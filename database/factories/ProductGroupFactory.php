<?php

namespace Database\Factories;

use App\Models\ProductGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductGroupFactory extends Factory
{
    protected $model = ProductGroup::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,  // Generate a random name for the product group
            'remark' => $this->faker->sentence,  // Generate a random remark for the product group
        ];
    }
}
