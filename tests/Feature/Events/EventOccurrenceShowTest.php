<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAirport;
use App\Models\EventOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOccurrenceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_details_page_renders_published_event(): void
    {
        $event = Event::factory()->published()->create([
            'title' => 'Capital Overload',
            'description' => "Our busiest night of the year.\n\nSix airports take part.",
        ]);
        EventAirport::factory()->for($event)->featured()->create(['icao_code' => 'EXAA']);
        EventAirport::factory()->for($event)->create(['icao_code' => 'EXBB']);
        $occurrence = EventOccurrence::factory()->for($event)->create([
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
        ]);

        $this->get("/events/{$occurrence->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/show')
                ->where('event.title', 'Capital Overload')
                ->where('event.airports.0.icao', 'EXAA')
                ->where('event.airports.0.is_featured', true)
                ->where('occurrence.id', $occurrence->id));
    }

    public function test_event_details_page_404s_for_draft_events(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);
        $occurrence = EventOccurrence::factory()->for($event)->create();

        $this->get("/events/{$occurrence->id}")->assertNotFound();
    }
}
