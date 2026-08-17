<?php

namespace Database\Factories;

use App\Models\ComparisonList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComparisonList>
 */
class ComparisonListFactory extends Factory
{
    protected $model = ComparisonList::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'list_type' => 'residential_sale',
        ];
    }
}
