<?php

namespace Database\Factories;

use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use App\Models\ResidentialProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComparisonItem>
 */
class ComparisonItemFactory extends Factory
{
    protected $model = ComparisonItem::class;

    public function definition(): array
    {
        return [
            'comparison_list_id' => ComparisonList::factory(),
            'comparable_type' => ResidentialProperty::class,
            'comparable_id' => ResidentialProperty::factory(),
            'added_at' => now(),
        ];
    }
}
