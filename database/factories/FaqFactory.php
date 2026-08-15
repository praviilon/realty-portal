<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['Общие вопросы', 'Размещение объявлений', 'Оплата']),
            'question' => fake()->sentence() . '?',
            'answer' => fake()->realText(150),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
