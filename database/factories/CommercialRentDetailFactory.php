<?php

namespace Database\Factories;

use App\Models\CommercialProperty;
use App\Models\CommercialRentDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialRentDetail>
 */
class CommercialRentDetailFactory extends Factory
{
    protected $model = CommercialRentDetail::class;

    public function definition(): array
    {
        return [
            'property_id' => CommercialProperty::factory(),
            'price_per_month' => fake()->numberBetween(20000, 3000000),
            'deposit' => fake()->optional()->numberBetween(20000, 500000),
            'commission' => fake()->optional()->numberBetween(0, 100000),
            'utilities_included' => fake()->boolean(),
            'rent_type' => fake()->randomElement(['direct', 'sublease']),
        ];
    }
}
