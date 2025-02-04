<?php

// database/factories/PrItemFactory.php

namespace Database\Factories;

use App\Models\PrItem;
use App\Models\PurchaseRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrItemFactory extends Factory
{
    protected $model = PrItem::class;

    public function definition()
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'product_id' => Product::factory(),
            'remark' => $this->faker->sentence,
            'qty' => $this->faker->randomFloat(2, 1, 100),
            'uom' => $this->faker->word,
            'unit_price' => $this->faker->randomFloat(2, 10, 100),
            'total_price' => $this->faker->randomFloat(2, 100, 1000),
            'campus' => $this->faker->word,
            'division' => $this->faker->word,
            'department' => $this->faker->word,
            'status' => 'pending',
        ];
    }
}

