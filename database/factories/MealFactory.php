<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meal>
 */
class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'unit' => fake()->word(),
            'category' => fake()->word(),
            'calories' => fake()->randomNumber(3),
            'tfat' => fake()->randomNumber(3),
            'sfat' => fake()->randomNumber(3),
            'carbs' => fake()->randomNumber(3),
            'sugar' => fake()->randomNumber(3),
            'protein' => fake()->randomNumber(3),
        ];
    }
}
