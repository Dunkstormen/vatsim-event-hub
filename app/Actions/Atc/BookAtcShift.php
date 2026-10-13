<?php

namespace App\Actions\Atc;

use App\Enums\AtcRosterType;
use App\Models\AtcShift;
use App\Models\AtcShiftBooking;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BookAtcShift
{
    /**
     * Book a pre-defined shift for a controller. Bookings only ever target
     * the event's next upcoming occurrence: booking a position this week
     * does not carry over to the following occurrence of a recurring event.
     * A controller cannot hold two overlapping shift bookings within the
     * same occurrence.
     */
    public function handle(User $user, AtcShift $shift): AtcShiftBooking
    {
        $position = $shift->position;
        $event = $position->event;

        if ($event->atc_roster_type !== AtcRosterType::PreDefined) {
            throw ValidationException::withMessages([
                'shift' => 'This event does not use pre-defined ATC shifts.',
            ]);
        }

        $occurrence = $event->nextUpcomingOccurrence();

        if ($occurrence === null) {
            throw ValidationException::withMessages([
                'shift' => 'There is no upcoming occurrence of this event to book a shift for.',
            ]);
        }

        $overlapping = AtcShiftBooking::query()
            ->where('event_occurrence_id', $occurrence->id)
            ->where('user_cid', $user->cid)
            ->with('shift')
            ->get()
            ->first(fn (AtcShiftBooking $booking) => $booking->shift->overlaps($shift));

        if ($overlapping !== null) {
            throw ValidationException::withMessages([
                'shift' => 'You already have an overlapping shift booked for this occurrence.',
            ]);
        }

        return AtcShiftBooking::firstOrCreate([
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
        ]);
    }
}
