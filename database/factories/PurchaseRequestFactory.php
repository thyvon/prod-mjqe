<?php

// database/factories/PurchaseRequestFactory.php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition()
    {
        return [
            'pr_number' => $this->faker->unique()->word,
            'request_date' => $this->faker->date(),
            'request_by' => User::factory(),
            'campus' => $this->faker->word,
            'division' => $this->faker->word,
            'department' => $this->faker->word,
            'purpose' => $this->faker->sentence,
            'is_urgent' => $this->faker->boolean,
            'is_cancel' => $this->faker->boolean,
            'total_item' => $this->faker->randomDigitNotNull,
            'total_amount' => $this->faker->randomFloat(2, 100, 1000),
            'status' => 'pending',
        ];
    }
}

