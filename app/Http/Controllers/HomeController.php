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
    private const FRONTPAGE_EVENT_LIMIT = 6;

    public function __invoke(Request $request): Response
    {
        $occurrences = EventOccurrence::query()
            ->with(['event.airports'])
            ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Published))
            ->where('status', EventOccurrenceStatus::Scheduled)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(self::FRONTPAGE_EVENT_LIMIT)
            ->get();

        $events = $occurrences->map(fn (EventOccurrence $occurrence) => $occurrence->toEventCardArray())->values();

        return Inertia::render('welcome', [
            'events' => $events,
            'nextEvent' => $events->first(),
        ]);
    }
}
