<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        // Insert sample categories into the product_categories table
        DB::table('product_categories')->insert([
            ['name' => 'Electronics'],
            ['name' => 'Furniture'],
            ['name' => 'Clothing'],
            ['name' => 'Home Appliances'],
            ['name' => 'Books'],
            ['name' => 'Beauty & Health'],
            ['name' => 'Toys'],
            ['name' => 'Groceries'],
            ['name' => 'Sports & Outdoors'],
            ['name' => 'Automotive'],
        ]);
    }
}
