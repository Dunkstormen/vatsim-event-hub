<?php

namespace App\Http\Controllers;

use App\Enums\EventOccurrenceStatus;
use App\Enums\EventStatus;
use App\Models\EventOccurrence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $occurrences = EventOccurrence::query()
            ->with(['event.airports'])
            ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Published))
            ->where('status', EventOccurrenceStatus::Scheduled)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(12)
            ->get();

        $events = $occurrences->map(function (EventOccurrence $occurrence) {
            $event = $occurrence->event;
            $airports = $event->airports
                ->sortByDesc('is_featured')
                ->pluck('icao_code')
                ->values();

            return [
                'id' => $occurrence->id,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $occurrence->starts_at->toIso8601String(),
                'ends_at' => $occurrence->ends_at->toIso8601String(),
                'airports' => $airports,
            ];
        })->values();

        return Inertia::render('welcome', [
            'events' => $events,
            'nextEvent' => $events->first(),
        ]);
    }
}
