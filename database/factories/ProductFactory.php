<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'sku' => $this->faker->unique()->word,  // Generate a unique SKU
            'product_description' => $this->faker->sentence,  // Random description
            'brand' => $this->faker->word,  // Random brand name
            'uom' => $this->faker->word,  // Random unit of measure
            'category_id' => ProductCategory::factory(),  // Create and associate a product category
            'group_id' => ProductGroup::factory(),  // Create and associate a product group
            'price' => $this->faker->randomFloat(2, 1, 100),  // Random price between 1 and 100
            'quantity' => $this->faker->randomFloat(2, 1, 100),  // Random quantity between 1 and 100
            'status' => $this->faker->randomElement([1, 0]),  // Random active or inactive status
        ];
    }
}
