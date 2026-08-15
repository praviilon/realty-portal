<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'listable_type' => ResidentialProperty::class,
            'listable_id' => ResidentialProperty::factory(),
        ];
    }
}
