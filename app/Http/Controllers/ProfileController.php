<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Account;

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

        // Account model mutator automatically hashes the password
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
}