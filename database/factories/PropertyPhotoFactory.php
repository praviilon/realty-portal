<?php

namespace Database\Factories;

use App\Models\PropertyPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyPhoto>
 */
class PropertyPhotoFactory extends Factory
{
    protected $model = PropertyPhoto::class;

    public function definition(): array
    {
        return [
            'path' => 'property-photos/' . fake()->uuid() . '.webp',
            'is_main' => false,
            'sort_order' => 0,
        ];
    }
}
