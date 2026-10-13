<?php

namespace Database\Factories;

use App\Models\Fir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fir>
 */
class FirFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('????')),
            'name' => $this->faker->city().' FIR',
        ];
    }
}
