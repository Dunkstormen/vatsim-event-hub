<?php

namespace Database\Factories;

use App\Models\AtcShift;
use App\Models\AtcShiftBooking;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtcShiftBooking>
 */
class AtcShiftBookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'atc_shift_id' => AtcShift::factory(),
            'event_occurrence_id' => EventOccurrence::factory(),
            'user_cid' => User::factory(),
        ];
    }
}
