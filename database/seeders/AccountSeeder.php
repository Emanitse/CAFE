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
            'username' => 'cashier',
            'email' => 'cashier@cafe.local',
            'password' => 'cashier123',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }
}