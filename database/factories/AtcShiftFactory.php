<?php

namespace Database\Factories;

use App\Models\AtcPosition;
use App\Models\AtcShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtcShift>
 */
class AtcShiftFactory extends Factory
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
            'label' => 'Early',
            'start_offset_minutes' => 0,
            'end_offset_minutes' => 120,
            'sort_order' => 0,
        ];
    }
}
