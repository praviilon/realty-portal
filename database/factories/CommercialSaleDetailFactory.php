<?php

namespace Database\Factories;

use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialSaleDetail>
 */
class CommercialSaleDetailFactory extends Factory
{
    protected $model = CommercialSaleDetail::class;

    public function definition(): array
    {
        return [
            'property_id' => CommercialProperty::factory(),
            'price' => fake()->numberBetween(1000000, 200000000),
            'commission' => fake()->optional()->numberBetween(0, 500000),
        ];
    }
}
