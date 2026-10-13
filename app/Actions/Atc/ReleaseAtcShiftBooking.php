<?php

namespace App\Actions\Atc;

use App\Models\AtcShiftBooking;

class ReleaseAtcShiftBooking
{
    public function handle(AtcShiftBooking $booking): void
    {
        $booking->delete();
    }
}
