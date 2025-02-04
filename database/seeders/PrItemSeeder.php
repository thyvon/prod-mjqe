<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrItem;

class PrItemSeeder extends Seeder
{
    public function run()
    {
        \App\Models\PrItem::factory(10)->create();
    }
}
