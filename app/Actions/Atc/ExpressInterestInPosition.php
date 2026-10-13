<?php

namespace App\Actions\Atc;

use App\Enums\AtcRosterType;
use App\Models\AtcPosition;
use App\Models\AtcPositionInterest;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ExpressInterestInPosition
{
    /**
     * Record (or update) a controller's interest in staffing a position for
     * a given occurrence. Only valid for open-interest events, and only
     * while that occurrence's interest window is open.
     */
    public function handle(User $user, EventOccurrence $occurrence, AtcPosition $position, ?string $availability): AtcPositionInterest
    {
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

        if (! $occurrence->isAtcInterestWindowOpen()) {
            throw ValidationException::withMessages([
                'position' => 'The interest window for this occurrence is not currently open.',
            ]);
        }

        return AtcPositionInterest::updateOrCreate(
            [
                'atc_position_id' => $position->id,
                'event_occurrence_id' => $occurrence->id,
                'user_cid' => $user->cid,
            ],
            [
                'availability' => $availability,
            ]
        );
    }
}
