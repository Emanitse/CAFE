<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Category 1: Hot Coffee
        MenuItem::create(['categoryID' => 1, 'itemname' => 'Espresso', 'price' => 100.00, 'stock' => 50]);
        MenuItem::create(['categoryID' => 1, 'itemname' => 'Americano', 'price' => 120.00, 'stock' => 50]);
        MenuItem::create(['categoryID' => 1, 'itemname' => 'Cafe Latte', 'price' => 140.00, 'stock' => 50]);

        // Category 2: Iced Drinks
        MenuItem::create(['categoryID' => 2, 'itemname' => 'Iced Latte', 'price' => 150.00, 'stock' => 50]);
        MenuItem::create(['categoryID' => 2, 'itemname' => 'Iced Macchiato', 'price' => 160.00, 'stock' => 40]);

        // Category 3: Pastries
        MenuItem::create(['categoryID' => 3, 'itemname' => 'Butter Croissant', 'price' => 85.00, 'stock' => 30]);
        MenuItem::create(['categoryID' => 3, 'itemname' => 'Chocolate Cookie', 'price' => 60.00, 'stock' => 25]);

        // Category 4: Add-ons
        MenuItem::create(['categoryID' => 4, 'itemname' => 'Extra Shot', 'price' => 30.00, 'stock' => 100]);
        MenuItem::create(['categoryID' => 4, 'itemname' => 'Whipped Cream', 'price' => 20.00, 'stock' => 100]);
    }
}
