<?php

namespace App\Http\Controllers;

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
            ->upcomingPublished()
            ->limit(self::FRONTPAGE_EVENT_LIMIT)
            ->get();

        $events = $occurrences->map(fn (EventOccurrence $occurrence) => $occurrence->toEventCardArray())->values();

        return Inertia::render('welcome', [
            'events' => $events,
            'nextEvent' => $events->first(),
        ]);
    }
}
