<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
public function run(): void
    {
        Category::create(['categoryname' => 'Hot Coffee', 'is_active' => true]);  // ID 1
        Category::create(['categoryname' => 'Iced Drinks', 'is_active' => true]); // ID 2
        Category::create(['categoryname' => 'Pastries', 'is_active' => true]);    // ID 3
        Category::create(['categoryname' => 'Add-ons', 'is_active' => true]);     // ID 4
    }
}