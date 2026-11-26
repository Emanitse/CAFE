<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Services\AuditLogger;
use App\Services\TotpService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $lockoutDuration = 10;

    public function showLogin()
    {
        if (session()->has('2fa_pending_accountID')) {
            return redirect()->route('login.2fa');
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $this->validateLoginRequest($request);

        $lockoutKey = $this->getLockoutKey($request);
        
        if ($this->isLockedOut($lockoutKey)) {
            $lockoutData = $this->getLockoutData($lockoutKey);
            $seconds = max(0, $lockoutData['ttl'] ?? $this->lockoutDuration);
            $lockoutEndTime = $lockoutData['end_time'] ?? (time() + $seconds);

            AuditLogger::logAccess('Account Lockout', 'warning', $request, [
                'email' => $request->email,
                'details' => "Login attempt blocked during active lockout ({$seconds}s remaining).",
            ]);
            
            return back()->withErrors([
                'email' => "Too many failed attempts. Please try again in {$seconds} seconds."
            ])->withInput($request->only('email'))
              ->with('lockout_end_time', $lockoutEndTime);
        }

        $account = Account::withoutGlobalScope('active')
            ->where('email', $request->email)
            ->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            $this->recordFailedAttempt($lockoutKey);
            
            $attempts = Cache::get($lockoutKey, 0);
            $remaining = max(0, $this->maxAttempts - $attempts);
            
            if ($attempts >= $this->maxAttempts) {
                $lockoutEndTime = time() + $this->lockoutDuration;

                AuditLogger::logAccess('Account Locked', 'warning', $request, [
                    'accountID' => $account?->accountID,
                    'username' => $account?->username,
                    'email' => $request->email,
                    'role' => $account?->role,
                    'details' => "Account locked for {$this->lockoutDuration}s after {$attempts} failed login attempts.",
                ]);

                return back()->withErrors([
                    'email' => "Too many failed attempts. Please try again in {$this->lockoutDuration} seconds."
                ])->withInput($request->only('email'))
                  ->with('lockout_end_time', $lockoutEndTime);
            }

            AuditLogger::logAccess('Login Failed', 'failed', $request, [
                'accountID' => $account?->accountID,
                'username' => $account?->username,
                'email' => $request->email,
                'role' => $account?->role,
                'details' => $account
                    ? "Incorrect password entered ({$remaining} attempts remaining)."
                    : "Unrecognized email address ({$remaining} attempts remaining).",
            ]);
            
            return back()->withErrors([
                'email' => "Invalid email or password. {$remaining} attempts remaining."
            ])->withInput($request->only('email'));
        }

        if (!$account->is_active) {
            AuditLogger::logAccess('Deactivated Login Attempt', 'failed', $request, [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'details' => 'Login blocked because account is deactivated.',
            ]);

            return back()->withErrors([
                'email' => 'This account has been deactivated.'
            ])->withInput($request->only('email'));
        }

        $this->clearFailedAttempts($lockoutKey);
        $request->session()->forget('lockout_end_time');
        $request->session()->regenerate();

        // Check if 2FA is active on this account
        if ($account->two_factor_enabled && !empty($account->two_factor_secret)) {
            $request->session()->put('2fa_pending_accountID', $account->accountID);
            $request->session()->put('2fa_pending_email', $account->email);

            AuditLogger::logAccess('2FA Challenge Prompted', 'warning', $request, [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'details' => 'Password verified; awaiting 6-digit authenticator code.',
            ]);

            return redirect()->route('login.2fa');
        }

        $this->completeLoginSession($request, $account);

        AuditLogger::logAccess('Login Success', 'success', $request, [
            'accountID' => $account->accountID,
            'username' => $account->username,
            'email' => $account->email,
            'role' => $account->role,
            'details' => 'Authenticated with email and password.',
        ]);

        return redirect('/home');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (!$request->session()->has('2fa_pending_accountID')) {
            return redirect()->route('login');
        }

        return view('login', [
            'showTwoFactor' => true,
            'pendingEmail' => $request->session()->get('2fa_pending_email'),
        ]);
    }

    public function verifyTwoFactorChallenge(Request $request, TotpService $totp)
    {
        $pendingAccountId = $request->session()->get('2fa_pending_accountID');
        if (!$pendingAccountId) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your verification session expired. Please sign in again.',
            ]);
        }

        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'Please enter the 6-digit verification code.',
            'code.digits' => 'The verification code must be 6 digits.',
        ]);

        $account = Account::withoutGlobalScope('active')->find($pendingAccountId);

        if (!$account || !$account->is_active) {
            $request->session()->forget(['2fa_pending_accountID', '2fa_pending_email']);
            return redirect()->route('login')->withErrors([
                'email' => 'Account not found or deactivated.',
            ]);
        }

        if (!$account->two_factor_enabled || empty($account->two_factor_secret)) {
            $request->session()->forget(['2fa_pending_accountID', '2fa_pending_email']);
            $request->session()->regenerate();
            $this->completeLoginSession($request, $account);

            AuditLogger::logAccess('Login Success', 'success', $request, [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'details' => '2FA was reset by Superadmin; login completed.',
            ]);

            return redirect('/home');
        }

        try {
            $secret = Crypt::decryptString($account->two_factor_secret);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'code' => 'Unable to read 2FA secret. Ask a Superadmin to reset your 2FA.',
            ]);
        }

        if (!$totp->verifyCode($secret, $request->code)) {
            AuditLogger::logAccess('2FA Failed', 'failed', $request, [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'details' => 'Entered an invalid 6-digit authenticator code.',
            ]);

            return back()->withErrors([
                'code' => 'Invalid 6-digit code. Please check Microsoft Authenticator and try again.',
            ]);
        }

        $request->session()->forget(['2fa_pending_accountID', '2fa_pending_email']);
        $request->session()->regenerate();
        $this->completeLoginSession($request, $account);

        AuditLogger::logAccess('2FA Verified & Login', 'success', $request, [
            'accountID' => $account->accountID,
            'username' => $account->username,
            'email' => $account->email,
            'role' => $account->role,
            'details' => 'Completed 2-step verification via authenticator app.',
        ]);

        return redirect('/home');
    }

    public function cancelTwoFactorChallenge(Request $request)
    {
        $pendingId = $request->session()->get('2fa_pending_accountID');
        $pendingEmail = $request->session()->get('2fa_pending_email');

        if ($pendingId || $pendingEmail) {
            AuditLogger::logAccess('2FA Cancelled', 'warning', $request, [
                'accountID' => $pendingId,
                'email' => $pendingEmail,
                'details' => 'User cancelled 2FA challenge and returned to login.',
            ]);
        }

        $request->session()->forget(['2fa_pending_accountID', '2fa_pending_email']);
        return redirect()->route('login');
    }

    protected function completeLoginSession(Request $request, Account $account): void
    {
        $request->session()->put('accountID', $account->accountID);
        $request->session()->put('email', $account->email);
        $request->session()->put('username', $account->username);
        $request->session()->put('role', $account->role);
        $request->session()->put('profile_picture', $account->profile_picture);
    }

    public function showRegister()
    {
        $this->generateCaptcha();
        return view('login', ['showRegister' => true]);
    }

    public function register(Request $request)
    {
        $this->validateRegistrationRequest($request);

        $captchaValid = $this->validateCaptcha($request);
        if (!$captchaValid) {
            $this->generateCaptcha();

            AuditLogger::logAccess('Registration Failed', 'failed', $request, [
                'username' => $request->username,
                'email' => $request->email,
                'details' => 'Incorrect CAPTCHA answer submitted during registration.',
            ]);

            return back()->withErrors([
                'captcha' => 'Incorrect CAPTCHA answer. Please try again.'
            ])->withInput($request->only('username', 'email'));
        }

        $account = Account::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'staff',
            'is_active' => true,
        ]);

        AuditLogger::logAccess('Account Registered', 'success', $request, [
            'accountID' => $account->accountID,
            'username' => $account->username,
            'email' => $account->email,
            'role' => $account->role,
            'details' => 'New staff account registered via self-registration.',
        ]);

        $this->generateCaptcha();

        return redirect()->route('login')->with('success', 'Registration successful! Please log in.');
    }

    public function logout(Request $request)
    {
        if (session()->has('accountID')) {
            AuditLogger::logAccess('Logout', 'success', $request, [
                'details' => 'User signed out of the system.',
            ]);
        }

        $request->session()->flush();
        $request->session()->regenerate();
        return redirect('/login')->with('status', 'You have been logged out successfully.')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }

    protected function validateLoginRequest(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);
    }

    protected function validateRegistrationRequest(Request $request)
    {
        $request->validate(Account::registrationRules(), [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'captcha.required' => 'Please answer the CAPTCHA.',
            'captcha.numeric' => 'CAPTCHA answer must be a number.',
        ]);
    }

    protected function getLockoutKey(Request $request): string
    {
        $email = $request->input('email') ?? 'unknown';
        return 'login_attempts:' . Str::lower($email);
    }

    protected function isLockedOut(string $key): bool
    {
        return Cache::has($key . ':locked');
    }

    protected function getLockoutData(string $key): array
    {
        $ttl = Cache::get($key . ':ttl', $this->lockoutDuration);
        $endTime = session("lockout_end_time_{$key}");
        if (!$endTime) {
            $endTime = time() + $ttl;
            session(["lockout_end_time_{$key}" => $endTime]);
        }
        return [
            'ttl' => max(0, $endTime - time()),
            'end_time' => $endTime,
        ];
    }

    protected function recordFailedAttempt(string $key): void
    {
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, $this->lockoutDuration * 60);

        if ($attempts >= $this->maxAttempts) {
            Cache::put($key . ':locked', true, $this->lockoutDuration);
            Cache::put($key . ':ttl', $this->lockoutDuration, $this->lockoutDuration);
            
            $lockoutEndTime = time() + $this->lockoutDuration;
            session(["lockout_end_time_{$key}" => $lockoutEndTime]);
        }
    }

    protected function clearFailedAttempts(string $key): void
    {
        Cache::forget($key);
        Cache::forget($key . ':locked');
        Cache::forget($key . ':ttl');
        session()->forget("lockout_end_time_{$key}");
    }

    protected function generateCaptcha(): void
    {
        $num1 = random_int(1, 20);
        $num2 = random_int(1, 20);
        session(['captcha_answer' => $num1 * $num2]);
        session(['captcha_question' => "{$num1} X {$num2}"]);
    }

    protected function validateCaptcha(Request $request): bool
    {
        $answer = $request->input('captcha');
        $stored = session('captcha_answer');
        
        if ($answer !== null && $stored !== null && (int)$answer === (int)$stored) {
            return true;
        }
        return false;
    }
}