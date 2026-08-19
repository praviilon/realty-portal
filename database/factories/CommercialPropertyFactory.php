<?php

namespace Database\Factories;

use App\Models\CommercialProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommercialProperty>
 */
class CommercialPropertyFactory extends Factory
{
    protected $model = CommercialProperty::class;

    public function definition(): array
    {
        $totalFloors = fake()->numberBetween(1, 25);

        return [
            'user_id' => User::factory(),
            'deal_type' => fake()->randomElement(['sale', 'rent']),
            'purpose_type' => fake()->randomElement(['office', 'retail', 'warehouse', 'free']),
            'building_type' => fake()->randomElement(['administrative', 'business_center', 'residential', 'shopping_center']),
            'entrance_type' => fake()->randomElement(['separate', 'common']),
            'floor' => fake()->numberBetween(1, $totalFloors),
            'floor_features' => fake()->randomElements(['shop_window', 'high_traffic', 'parking', 'security'], 2),
            'total_floors' => $totalFloors,
            'area' => fake()->numberBetween(20, 2000),
            'ceiling_height' => fake()->randomFloat(2, 2.5, 6),
            'heating_type' => fake()->randomElement(['central', 'autonomous', 'none']),
            'finishing_type' => fake()->randomElement(['none', 'rough', 'fine']),
            'furniture' => fake()->randomElement(['none', 'partial', 'full']),
            'owner_type' => fake()->randomElement(['owner', 'agent']),
            'contact_type' => fake()->randomElement(['calls_and_messages', 'messages_only']),
            'address' => fake()->address(),
            'metro_station' => fake()->optional()->streetName(),
            'metro_distance_min' => fake()->optional()->numberBetween(1, 30),
            'lat' => fake()->latitude(55.5, 56.0),
            'lng' => fake()->longitude(37.3, 37.9),
            'description' => fake()->realText(200),
            'status' => 'active',
            'views_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function moderation(): static
    {
        return $this->state(fn () => ['status' => 'moderation']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejection_reason' => 'Некорректные фотографии объекта.',
        ]);
    }
}
