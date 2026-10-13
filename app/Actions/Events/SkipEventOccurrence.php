<?php

namespace App\Actions\Events;

use App\Enums\EventOccurrenceStatus;
use App\Models\EventOccurrence;

class SkipEventOccurrence
{
    public function handle(EventOccurrence $occurrence): EventOccurrence
    {
        $occurrence->update(['status' => EventOccurrenceStatus::Skipped]);

        return $occurrence;
    }
}
