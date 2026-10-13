<?php

namespace Database\Factories;

use App\Models\AtcPosition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtcPosition>
 */
class AtcPositionFactory extends Factory
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
            'callsign' => strtoupper($this->faker->lexify('????')).'_TWR',
            'frequency' => '118.100',
            'airport_icao' => strtoupper($this->faker->lexify('????')),
            'sort_order' => 0,
        ];
    }
}
