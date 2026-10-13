<?php

namespace App\Actions\Events;

use App\Enums\EventOccurrenceStatus;
use App\Models\Event;
use App\Models\EventOccurrence;
use Carbon\CarbonInterface;

class GenerateEventOccurrences
{
    /**
     * Number of months ahead to generate occurrences for a recurring event.
     */
    private const HORIZON_MONTHS = 6;

    /**
     * Generate the occurrences for an event. A non-recurring event gets a
     * single occurrence matching its own start/end. A recurring event gets
     * one occurrence per interval from its start date out to a 6 month
     * horizon.
     *
     * Idempotent: occurrences are matched on (event_id, starts_at), so
     * calling this again for the same event will not create duplicates.
     */
    public function handle(Event $event): void
    {
        $duration = $event->starts_at->diffInSeconds($event->ends_at);

        if (! $event->isRecurring()) {
            $this->createOccurrence($event, $event->starts_at, $event->ends_at);

            return;
        }

        $horizon = $event->starts_at->copy()->addMonths(self::HORIZON_MONTHS);
        $occurrenceStart = $event->starts_at->copy();

        while ($occurrenceStart->lessThanOrEqualTo($horizon)) {
            $this->createOccurrence(
                $event,
                $occurrenceStart->copy(),
                $occurrenceStart->copy()->addSeconds($duration)
            );

            $occurrenceStart = $event->recurrence_frequency->next($occurrenceStart);
        }
    }

    private function createOccurrence(Event $event, CarbonInterface $startsAt, CarbonInterface $endsAt): EventOccurrence
    {
        return EventOccurrence::firstOrCreate(
            [
                'event_id' => $event->id,
                'starts_at' => $startsAt,
            ],
            [
                'ends_at' => $endsAt,
                'status' => EventOccurrenceStatus::Scheduled,
            ]
        );
    }
}
