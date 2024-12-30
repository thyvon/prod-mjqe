<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Instantiate Faker for generating random data
        $faker = Faker::create();

        // Seed 10 suppliers
        foreach (range(1, 10) as $index) {
            DB::table('suppliers')->insert([
                'name' => $faker->company,
                'kh_name' => $faker->company,
                'number' => $faker->unique()->numerify('S#######'),
                'email' => $faker->unique()->safeEmail,
                'address' => $faker->address,
                'payment_term' => $faker->word,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
