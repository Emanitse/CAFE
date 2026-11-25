<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Account;

class UserController extends Controller
{
    // Load the User Management view
    public function index()
    {
        return view('components.users');
    }

    // Fetch users based on role visibility rules (including deactivated accounts)
    public function getUsers(Request $request)
    {
        $currentUserId = session('accountID');
        $currentRole = session('role');

        $query = Account::withoutGlobalScope('active');

        // Role visibility enforcement:
        // - Admin cannot see themselves, other admins, or superadmins (only staff)
        // - Superadmin can see all roles
        if ($currentRole === 'admin') {
            $query->where('role', 'staff')
                  ->where('accountID', '!=', $currentUserId);
        }

        // Optional status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $isActive = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }

        // Optional search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('accountID', 'asc')
            ->get(['accountID', 'username', 'email', 'role', 'is_active', 'two_factor_enabled'])
            ->map(function ($user) use ($currentUserId) {
                $user->is_self = ($user->accountID == $currentUserId);
                $user->two_factor_enabled = (bool) $user->two_factor_enabled;
                return $user;
            });

        return response()->json([
            'success' => true,
            'can_reset_2fa' => ($currentRole === 'superadmin'),
            'users' => $users,
        ]);
    }

    // Create a new user account
    public function store(Request $request)
    {
        $currentRole = session('role');

        $rules = [
            'username' => 'required|string|max:255|unique:accounts,username',
            'email' => 'required|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8',
            'role' => $currentRole === 'superadmin'
                ? 'required|in:staff,admin'
                : 'nullable|in:staff',
        ];

        $validated = $request->validate($rules, [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'role.in' => 'Invalid role selected.',
        ]);

        // Enforce role restriction: non-superadmins can only create staff accounts
        $assignedRole = ($currentRole === 'superadmin')
            ? $validated['role']
            : 'staff';

        // Account model mutator automatically hashes the password
        $account = Account::create([
            'username' => trim($validated['username']),
            'email' => trim($validated['email']),
            'password' => $validated['password'],
            'role' => $assignedRole,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User account created successfully.',
            'user' => [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'is_active' => $account->is_active,
                'two_factor_enabled' => false,
            ],
        ]);
    }

    // Update username, email, and optional password (skips DB update if unchanged)
    public function update(Request $request, $id)
    {
        $currentUserId = session('accountID');
        $currentRole = session('role');

        $account = Account::withoutGlobalScope('active')->findOrFail($id);

        // Authorization guard: Admin can only edit staff accounts
        if ($currentRole === 'admin' && ($account->role !== 'staff' || $account->accountID == $currentUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this account.',
            ], 403);
        }

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
            'password' => 'nullable|string|min:8',
        ], [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'New password must be at least 8 characters.',
        ]);

        $newUsername = trim($validated['username']);
        $newEmail = trim($validated['email']);
        $passwordProvided = $request->filled('password');

        // Check if values are identical / unchanged
        $usernameSame = ($newUsername === $account->username);
        $emailSame = (strcasecmp($newEmail, $account->email) === 0);
        $passwordSame = (!$passwordProvided || Hash::check($request->password, $account->password));

        if ($usernameSame && $emailSame && $passwordSame) {
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

        // Only pass password if changed (Account model mutator automatically hashes it)
        if ($passwordProvided && !$passwordSame) {
            $updateData['password'] = $request->password;
        }

        $account->update($updateData);

        // Keep current session synced if superadmin edited their own profile
        if ($account->accountID == $currentUserId) {
            session([
                'username' => $account->username,
                'email' => $account->email,
            ]);
        }

        return response()->json([
            'success' => true,
            'unchanged' => false,
            'message' => 'User updated successfully.',
            'user' => [
                'accountID' => $account->accountID,
                'username' => $account->username,
                'email' => $account->email,
                'role' => $account->role,
                'is_active' => $account->is_active,
            ],
        ]);
    }

    // Soft delete (De-Activate) or restore (Re-Activate) user account
    public function toggleStatus($id)
    {
        $currentUserId = session('accountID');
        $currentRole = session('role');

        $account = Account::withoutGlobalScope('active')->findOrFail($id);

        // Prevent self-deactivation
        if ($account->accountID == $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        // Authorization guard: Admin can only toggle staff accounts
        if ($currentRole === 'admin' && $account->role !== 'staff') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this account status.',
            ], 403);
        }

        $account->update([
            'is_active' => !$account->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $account->is_active,
            'message' => $account->is_active ? 'Account re-activated.' : 'Account deactivated.',
        ]);
    }

    // Superadmin-only emergency reset for a user's Two-Factor Authentication
    public function resetTwoFactor($id)
    {
        if (session('role') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only a Superadmin can reset Two-Factor Authentication.',
            ], 403);
        }

        $account = Account::withoutGlobalScope('active')->findOrFail($id);

        if (!$account->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-Factor Authentication is already disabled for this account.',
            ], 422);
        }

        $account->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Two-Factor Authentication has been reset for {$account->username}.",
        ]);
    }
}