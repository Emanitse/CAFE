<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Account;
use App\Services\TotpService;

class ProfileController extends Controller
{
    public function index()
    {
        $account = Account::withoutGlobalScope('active')
            ->findOrFail(session('accountID'));

        session([
            'username' => $account->username,
            'email' => $account->email,
        ]);

        return view('components.profile', compact('account'));
    }

    public function update(Request $request)
    {
        $account = Account::withoutGlobalScope('active')
            ->findOrFail(session('accountID'));

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'username')->ignore($account->accountID, 'accountID'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('accounts', 'email')->ignore($account->accountID, 'accountID'),
            ],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ], [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'current_password.required_with' => 'Please enter your current password to set a new one.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
        ]);

        $newUsername = trim($validated['username']);
        $newEmail = trim($validated['email']);
        $hasNewPassword = $request->filled('new_password');

        if ($hasNewPassword) {
            if (!Hash::check($request->current_password, $account->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your current password is incorrect.',
                ], 422);
            }

            if (Hash::check($request->new_password, $account->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from your current password.',
                ], 422);
            }
        }

        if (
            !$hasNewPassword &&
            $newUsername === $account->username &&
            strcasecmp($newEmail, $account->email) === 0
        ) {
            return response()->json([
                'success' => true,
                'unchanged' => true,
                'message' => 'No changes were made.',
            ]);
        }

        $updateData = [
            'username' => $newUsername,
            'email' => $newEmail,
        ];

        if ($hasNewPassword) {
            $updateData['password'] = $request->new_password;
        }

        $account->update($updateData);

        session([
            'username' => $account->username,
            'email' => $account->email,
        ]);

        return response()->json([
            'success' => true,
            'unchanged' => false,
            'message' => 'Profile updated successfully.',
            'user' => [
                'username' => $account->username,
                'email' => $account->email,
            ],
        ]);
    }

    // Step A: Generate a temporary TOTP secret & otpauth URI for QR scanning
    public function setupTwoFactor(TotpService $totp)
    {
        $account = Account::withoutGlobalScope('active')
            ->findOrFail(session('accountID'));

        $secret = $totp->generateSecret();
        session(['pending_2fa_secret' => $secret]);

        $label = $account->email ?: $account->username;
        $qrUri = $totp->getProvisioningUri('Cafe Ordering System', $label, $secret);

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qrUri' => $qrUri,
        ]);
    }

    // Step B: Verify the 6-digit Microsoft Authenticator code and activate 2FA
    public function confirmTwoFactor(Request $request, TotpService $totp)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'Please enter the 6-digit verification code.',
            'code.digits' => 'The verification code must be 6 digits.',
        ]);

        $pendingSecret = session('pending_2fa_secret');
        if (!$pendingSecret) {
            return response()->json([
                'success' => false,
                'message' => '2FA setup session expired. Please click Enable 2FA again.',
            ], 422);
        }

        if (!$totp->verifyCode($pendingSecret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid 6-digit code. Ensure your device clock is accurate and try again.',
            ], 422);
        }

        $account = Account::withoutGlobalScope('active')
            ->findOrFail(session('accountID'));

        $account->update([
            'two_factor_secret' => Crypt::encryptString($pendingSecret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        session()->forget('pending_2fa_secret');

        return response()->json([
            'success' => true,
            'message' => 'Two-Factor Authentication is now enabled on your account.',
        ]);
    }

    // Disable 2FA after confirming current password
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Please enter your current password to disable 2FA.',
        ]);

        $account = Account::withoutGlobalScope('active')
            ->findOrFail(session('accountID'));

        if (!Hash::check($request->password, $account->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password.',
            ], 422);
        }

        $account->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        session()->forget('pending_2fa_secret');

        return response()->json([
            'success' => true,
            'message' => 'Two-Factor Authentication has been disabled.',
        ]);
    }
}