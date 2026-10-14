<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAirport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_no_events(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('events', [])
                ->where('nextEvent', null));
    }

    public function test_home_page_lists_upcoming_published_events_with_airports(): void
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

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('events', 1)
                ->where('events.0.title', 'Capital Overload')
                ->where('events.0.airports', ['EXAA', 'EXBB'])
                ->where('nextEvent.title', 'Capital Overload'));
    }

    public function test_home_page_limits_events_to_six(): void
    {
        Event::factory()
            ->published()
            ->count(8)
            ->sequence(fn ($sequence) => ['starts_at' => now()->addDays($sequence->index + 1)])
            ->create();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('events', 6));
    }

    public function test_home_page_excludes_past_events(): void
    {
        Event::factory()->published()->create([
            'title' => 'Past Event',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subWeek()->addHours(2),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('events', []));
    }
}
