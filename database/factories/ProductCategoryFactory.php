<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,  // Generate a random name for the product category
            'remark' => $this->faker->sentence,  // Generate a random remark for the product category
        ];
    }
}
