<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $lockoutDuration = 10;

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $this->validateLoginRequest($request);

        $lockoutKey = $this->getLockoutKey($request);
        
        if ($this->isLockedOut($lockoutKey)) {
            $seconds = Cache::get($lockoutKey . ':ttl');
            return back()->withErrors([
                'email' => "Too many failed attempts. Please try again in {$seconds} seconds."
            ])->withInput($request->only('email'));
        }

        $account = Account::withoutGlobalScope('active')
            ->where('email', $request->email)
            ->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            $this->recordFailedAttempt($lockoutKey);
            
            $remaining = $this->maxAttempts - Cache::get($lockoutKey, 0);
            return back()->withErrors([
                'email' => "Invalid email or password. {$remaining} attempts remaining."
            ])->withInput($request->only('email'));
        }

        if (!$account->is_active) {
            return back()->withErrors([
                'email' => 'This account has been deactivated.'
            ])->withInput($request->only('email'));
        }

        $this->clearFailedAttempts($lockoutKey);
        $request->session()->regenerate();

        $request->session()->put('accountID', $account->accountID);
        $request->session()->put('email', $account->email);
        $request->session()->put('username', $account->username);
        $request->session()->put('role', $account->role);

        return redirect('/home');
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

        $this->generateCaptcha();

        return redirect()->route('login')->with('success', 'Registration successful! Please log in.');
    }

    public function logout(Request $request)
    {
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
        return 'login_attempts:' . Str::lower($email) . ':' . $request->ip();
    }

    protected function isLockedOut(string $key): bool
    {
        return Cache::has($key . ':locked');
    }

    protected function recordFailedAttempt(string $key): void
    {
        $attempts = Cache::increment($key);
        
        if ($attempts === 1) {
            Cache::expire($key, $this->lockoutDuration * 60);
        }

        if ($attempts >= $this->maxAttempts) {
            Cache::put($key . ':locked', true, $this->lockoutDuration);
            Cache::put($key . ':ttl', $this->lockoutDuration, $this->lockoutDuration);
        }
    }

    protected function clearFailedAttempts(string $key): void
    {
        Cache::forget($key);
        Cache::forget($key . ':locked');
        Cache::forget($key . ':ttl');
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