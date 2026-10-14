<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAirport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_index_renders_with_no_events(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/index')
                ->where('events', []));
    }

    public function test_event_index_lists_upcoming_published_events_with_airports(): void
    {
        $event = Event::factory()->published()->create([
            'title' => 'Capital Overload',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
        ]);
        EventAirport::factory()->for($event)->featured()->create(['icao_code' => 'EXAA']);
        EventAirport::factory()->for($event)->create(['icao_code' => 'EXBB']);

        Event::factory()->create([
            'status' => EventStatus::Draft,
            'title' => 'Hidden Draft Event',
            'starts_at' => now()->addWeek(),
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/index')
                ->has('events', 1)
                ->where('events.0.title', 'Capital Overload')
                ->where('events.0.airports', ['EXAA', 'EXBB']));
    }

    public function test_event_index_excludes_past_events(): void
    {
        Event::factory()->published()->create([
            'title' => 'Past Event',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subWeek()->addHours(2),
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/index')
                ->where('events', []));
    }

    public function test_event_index_is_not_limited_to_six_events(): void
    {
        Event::factory()
            ->published()
            ->count(8)
            ->sequence(fn ($sequence) => ['starts_at' => now()->addDays($sequence->index + 1)])
            ->create();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/index')
                ->has('events', 8)
                ->where('nextPage', null));
    }

    public function test_event_index_paginates_and_exposes_next_page(): void
    {
        Event::factory()
            ->published()
            ->count(10)
            ->sequence(fn ($sequence) => ['starts_at' => now()->addDays($sequence->index + 1)])
            ->create();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/index')
                ->has('events', 9)
                ->where('nextPage', 2));
    }

    public function test_event_feed_returns_the_next_page_of_events(): void
    {
        Event::factory()
            ->published()
            ->count(10)
            ->sequence(fn ($sequence) => ['starts_at' => now()->addDays($sequence->index + 1)])
            ->create();

        $response = $this->getJson(route('events.feed', ['page' => 2]))
            ->assertOk();

        $response->assertJsonCount(1, 'events');
        $response->assertJson(['nextPage' => null]);
    }
}
