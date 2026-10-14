<?php

namespace App\Http\Controllers;

use App\Actions\Events\SaveEvent;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('events/create');
    }

    public function store(EventRequest $request, SaveEvent $saveEvent): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $saveEvent->handle(null, $request->safe()->except('banner'), $request->file('banner'));

        return to_route('events.index');
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('events/edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'status' => $event->status->value,
                'starts_at' => $event->starts_at->toIso8601String(),
                'ends_at' => $event->ends_at->toIso8601String(),
                'recurrence_frequency' => $event->recurrence_frequency?->value,
                'banner_path' => $event->banner_path,
            ],
        ]);
    }

    public function update(EventRequest $request, Event $event, SaveEvent $saveEvent): RedirectResponse
    {
        $this->authorize('update', $event);

        $saveEvent->handle($event, $request->safe()->except('banner'), $request->file('banner'));

        return to_route('events.index');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return to_route('events.index');
    }
}
