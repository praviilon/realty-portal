<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspacePricing>
 */
class WorkspacePricingFactory extends Factory
{
    protected $model = WorkspacePricing::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'period' => fake()->randomElement(['hour', 'day', 'week', 'month']),
            'price' => fake()->numberBetween(300, 100000),
        ];
    }
}
