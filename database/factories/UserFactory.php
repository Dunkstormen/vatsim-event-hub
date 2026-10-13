<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cid' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'rating' => fake()->numberBetween(1, 12),
            'area' => fake()->randomElement(['EUR', 'AMAS', 'APAC', 'AFRIC', 'MENA']),
            'division' => fake()->randomElement(['EUD', 'USA', 'CAN']),
            'subdivision' => fake()->optional()->lexify('???'),
            'remember_token' => Str::random(10),
        ];
    }
}
