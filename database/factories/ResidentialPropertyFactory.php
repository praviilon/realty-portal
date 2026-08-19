<?php

namespace Database\Factories;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResidentialProperty>
 */
class ResidentialPropertyFactory extends Factory
{
    protected $model = ResidentialProperty::class;

    public function definition(): array
    {
        $totalFloors = fake()->numberBetween(5, 25);
        $dealType = fake()->randomElement(['sale', 'rent']);

        return [
            'user_id' => User::factory(),
            'deal_type' => $dealType,
            'property_type' => fake()->randomElement(['apartment', 'house', 'room', 'studio']),
            'address' => fake()->address(),
            'lat' => fake()->latitude(55.5, 56.0),
            'lng' => fake()->longitude(37.3, 37.9),
            'metro_station' => fake()->optional()->streetName(),
            'metro_distance_min' => fake()->optional()->numberBetween(1, 30),
            'area' => fake()->numberBetween(18, 150),
            'floor' => fake()->numberBetween(1, $totalFloors),
            'total_floors' => $totalFloors,
            'heating_type' => fake()->randomElement(['central', 'autonomous', 'none']),
            'finishing_type' => fake()->randomElement(['none', 'rough', 'fine']),
            'furniture' => fake()->randomElement(['none', 'partial', 'full']),
            'floor_features' => fake()->randomElement([[], ['no_elevator']]),
            'price' => fake()->numberBetween(15000, 25000000),
            'deposit' => $dealType === 'rent' ? fake()->optional()->numberBetween(10000, 100000) : null,
            'commission' => fake()->optional()->numberBetween(5000, 50000),
            'rent_type' => $dealType === 'rent' ? fake()->randomElement(['direct', 'sublease']) : null,
            'utilities_included' => $dealType === 'rent' ? fake()->boolean() : false,
            'owner_type' => fake()->randomElement(['owner', 'agent']),
            'contact_type' => fake()->randomElement(['calls_and_messages', 'messages_only']),
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
