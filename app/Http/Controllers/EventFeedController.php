<?php

namespace App\Http\Controllers;

use App\Models\EventOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventFeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $year = $request->query('year');

        $paginator = EventOccurrence::query()
            ->upcomingPublished()
            ->when($year, fn ($query) => $query->whereYear('starts_at', $year))
            ->paginate(EventIndexController::PER_PAGE, page: (int) $request->query('page', 1));

        $events = collect($paginator->items())
            ->map(fn (EventOccurrence $occurrence) => $occurrence->toEventCardArray())
            ->values();

        return response()->json([
            'events' => $events,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }
}
