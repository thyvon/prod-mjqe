<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductGroupSeeder extends Seeder
{
    public function run()
    {
        // Insert sample groups into the product_groups table
        DB::table('product_groups')->insert([
            ['name' => 'Smartphones'],
            ['name' => 'Laptops'],
            ['name' => 'Sofas'],
            ['name' => 'Shirts'],
            ['name' => 'Washing Machines'],
            ['name' => 'Hair Care'],
            ['name' => 'Headphones'],
            ['name' => 'Kitchen Appliances'],
            ['name' => 'Outdoor Furniture'],
            ['name' => 'Fitness Equipment'],
        ]);
    }
}
