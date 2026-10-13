<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAirport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAirport>
 */
class EventAirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'icao_code' => strtoupper($this->faker->unique()->lexify('????')),
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
