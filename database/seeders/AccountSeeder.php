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
            'password' => Hash::make('admin123'),
            'role' => 'admin'
        ]);


        Account::create([
            'username' => 'cashier',
            'password' => Hash::make('cashier123'),
            'role' => 'staff'
        ]);
    }
}