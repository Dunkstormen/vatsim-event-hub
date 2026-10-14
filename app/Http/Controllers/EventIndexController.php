<?php

namespace App\Http\Controllers;

use App\Enums\EventOccurrenceStatus;
use App\Enums\EventStatus;
use App\Models\EventOccurrence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $occurrences = EventOccurrence::query()
            ->with(['event.airports'])
            ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Published))
            ->where('status', EventOccurrenceStatus::Scheduled)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();

        $events = $occurrences->map(fn (EventOccurrence $occurrence) => $occurrence->toEventCardArray())->values();

        return Inertia::render('events/index', [
            'events' => $events,
        ]);
    }
}
