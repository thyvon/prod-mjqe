<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseRequest;

class PurchaseRequestSeeder extends Seeder
{
    public function run()
    {
        \App\Models\PurchaseRequest::factory(5)->create();
    }
}
