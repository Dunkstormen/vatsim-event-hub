<?php

namespace App\Actions\Events;

use App\Enums\EventOccurrenceStatus;
use App\Models\EventOccurrence;

class CancelEventOccurrence
{
    public function handle(EventOccurrence $occurrence): EventOccurrence
    {
        $occurrence->update(['status' => EventOccurrenceStatus::Cancelled]);

        return $occurrence;
    }
}
