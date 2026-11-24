<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::create([
            'username' => 'admin',
            'email' => 'admin@cafe.local',
            'password' => 'admin123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        Account::create([
            'username' => 'staff',
            'email' => 'staff@cafe.local',
            'password' => 'staff123',
            'role' => 'staff',
            'is_active' => true,
        ]);

        Account::create([
            'username' => 'manager',
            'email' => 'manager@cafe.local',
            'password' => 'manager123',
            'role' => 'manager',
            'is_active' => true,
        ]);

        Account::create([
            'username' => 'superadmin',
            'email' => 'superadmin@cafe.local',
            'password' => 'superadmin123',
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }
}