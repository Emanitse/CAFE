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
            'email' => 'admin@cafe.local',
            'password' => 'admin123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        Account::create([
            'email' => 'cashier@cafe.local',
            'password' => 'cashier123',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }
}