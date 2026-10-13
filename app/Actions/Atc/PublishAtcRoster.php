<?php

namespace App\Actions\Atc;

use App\Enums\AtcRosterType;
use App\Models\EventOccurrence;
use Illuminate\Validation\ValidationException;

class PublishAtcRoster
{
    /**
     * Publish (or re-publish, after a revision) the roster for an
     * occurrence, making it visible to everyone. Staff may keep revising
     * the underlying assignments up until the occurrence starts.
     */
    public function handle(EventOccurrence $occurrence): EventOccurrence
    {
        if ($occurrence->event->atc_roster_type !== AtcRosterType::OpenInterest) {
            throw ValidationException::withMessages([
                'occurrence' => 'This event does not use open-interest ATC rostering.',
            ]);
        }

        $occurrence->update(['atc_roster_published_at' => now()]);

        return $occurrence;
    }
}
