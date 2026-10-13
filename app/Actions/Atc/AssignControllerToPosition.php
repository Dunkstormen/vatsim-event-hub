<?php

namespace App\Actions\Atc;

use App\Enums\AtcRosterType;
use App\Models\AtcPosition;
use App\Models\AtcRosterAssignment;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignControllerToPosition
{
    /**
     * Staff-driven roster assignment for an open-interest event. Staff may
     * create or revise assignments up until the occurrence starts, including
     * after the roster has already been published.
     */
    public function handle(
        EventOccurrence $occurrence,
        AtcPosition $position,
        User $user,
        ?\DateTimeInterface $startsAt,
        ?\DateTimeInterface $endsAt,
    ): AtcRosterAssignment {
        $event = $occurrence->event;

        if ($event->atc_roster_type !== AtcRosterType::OpenInterest) {
            throw ValidationException::withMessages([
                'position' => 'This event does not use open-interest ATC rostering.',
            ]);
        }

        if ($position->event_id !== $event->id) {
            throw ValidationException::withMessages([
                'position' => 'This position does not belong to this event.',
            ]);
        }

        if ($occurrence->starts_at->isPast()) {
            throw ValidationException::withMessages([
                'position' => 'This occurrence has already started.',
            ]);
        }

        return AtcRosterAssignment::create([
            'atc_position_id' => $position->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
