<?php

namespace App\Http\Controllers;

use App\Models\EventOccurrence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventIndexController extends Controller
{
    public const PER_PAGE = 9;

    public function __invoke(Request $request): Response
    {
        $year = $request->query('year');

        $paginator = EventOccurrence::query()
            ->upcomingPublished()
            ->when($year, fn ($query) => $query->whereYear('starts_at', $year))
            ->paginate(self::PER_PAGE);

        $events = collect($paginator->items())
            ->map(fn (EventOccurrence $occurrence) => $occurrence->toEventCardArray())
            ->values();

        $years = EventOccurrence::query()
            ->upcomingPublished()
            ->get(['starts_at'])
            ->map(fn (EventOccurrence $occurrence) => $occurrence->starts_at->format('Y'))
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('events/index', [
            'events' => $events,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'years' => $years,
            'year' => $year,
        ]);
    }
}
