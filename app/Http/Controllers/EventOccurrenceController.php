<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\EventOccurrence;
use Inertia\Inertia;
use Inertia\Response;

class EventOccurrenceController extends Controller
{
    public function show(EventOccurrence $eventOccurrence): Response
    {
        $eventOccurrence->loadMissing(['event.airports']);
        $event = $eventOccurrence->event;

        abort_unless($event->status === EventStatus::Published, 404);

        $airports = $event->airports
            ->sortByDesc('is_featured')
            ->map(fn ($airport) => [
                'icao' => $airport->icao_code,
                'is_featured' => $airport->is_featured,
            ])
            ->values();

        return Inertia::render('events/show', [
            'occurrence' => [
                'id' => $eventOccurrence->id,
                'status' => $eventOccurrence->status->value,
                'starts_at' => $eventOccurrence->starts_at->toIso8601String(),
                'ends_at' => $eventOccurrence->ends_at->toIso8601String(),
            ],
            'event' => [
                'title' => $event->title,
                'description' => $event->description,
                'recurrence_frequency' => $event->recurrence_frequency?->value,
                'airports' => $airports,
            ],
        ]);
    }
}
