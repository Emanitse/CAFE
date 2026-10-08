<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create(['categoryname' => 'Hot Coffee']);  // ID 1
        Category::create(['categoryname' => 'Iced Drinks']); // ID 2
        Category::create(['categoryname' => 'Pastries']);    // ID 3
        Category::create(['categoryname' => 'Add-ons']);     // ID 4
    }
}