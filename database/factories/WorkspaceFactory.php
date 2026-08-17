<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $totalFloors = fake()->numberBetween(1, 25);

        return [
            'user_id' => User::factory(),
            'workspace_type' => fake()->randomElement(['workspace', 'office', 'meeting_room', 'conference_room']),
            'workspace_subtype' => fake()->randomElement(['fixed', 'flexible']),
            'building_type' => fake()->randomElement(['administrative', 'business_center', 'residential', 'shopping_center']),
            'entrance_type' => fake()->randomElement(['separate', 'common']),
            'floor' => fake()->numberBetween(1, $totalFloors),
            'total_floors' => $totalFloors,
            'floor_features' => fake()->randomElements(['separate_entrance', 'parking', 'security', 'reception'], 2),
            'area' => fake()->numberBetween(5, 500),
            'access_time' => [['type' => 'weekdays', 'time_from' => '09:00', 'time_to' => '20:00']],
            'amenities' => fake()->randomElements(['wifi', 'coffee', 'kitchen', 'printer', 'whiteboard'], 3),
            'extra_options' => fake()->randomElements(['cleaning', 'reception_service', 'tech_support'], 1),
            'address' => fake()->address(),
            'lat' => fake()->latitude(55.5, 56.0),
            'lng' => fake()->longitude(37.3, 37.9),
            'metro_station' => fake()->optional()->streetName(),
            'metro_distance_min' => fake()->optional()->numberBetween(1, 30),
            'description' => fake()->realText(200),
            'status' => 'active',
            'deposit' => fake()->optional()->numberBetween(1000, 50000),
            'utilities_included' => fake()->boolean(),
            'owner_type' => fake()->randomElement(['owner', 'agent']),
            'contact_type' => fake()->randomElement(['calls_and_messages', 'messages_only']),
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
