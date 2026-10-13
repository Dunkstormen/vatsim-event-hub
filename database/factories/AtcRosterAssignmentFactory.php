<?php

namespace Database\Factories;

use App\Models\AtcPosition;
use App\Models\AtcRosterAssignment;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtcRosterAssignment>
 */
class AtcRosterAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'atc_position_id' => AtcPosition::factory(),
            'event_occurrence_id' => EventOccurrence::factory(),
            'user_cid' => User::factory(),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
