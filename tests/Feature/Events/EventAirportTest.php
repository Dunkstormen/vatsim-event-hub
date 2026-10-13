<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventAirport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAirportTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_event_separates_featured_and_participating_airports()
    {
        $event = Event::factory()->create();

        EventAirport::factory()->for($event)->featured()->create(['icao_code' => 'EGLL']);
        EventAirport::factory()->for($event)->create(['icao_code' => 'EGKK']);

        $this->assertSame(['EGLL'], $event->featuredAirports()->pluck('icao_code')->all());
        $this->assertSame(['EGKK'], $event->participatingAirports()->pluck('icao_code')->all());
    }
}
