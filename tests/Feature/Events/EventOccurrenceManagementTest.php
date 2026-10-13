<?php

namespace Tests\Feature\Events;

use App\Actions\Events\CancelEventOccurrence;
use App\Actions\Events\SkipEventOccurrence;
use App\Enums\EventOccurrenceStatus;
use App\Models\EventOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOccurrenceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_occurrence_can_be_cancelled()
    {
        $occurrence = EventOccurrence::factory()->create(['status' => EventOccurrenceStatus::Scheduled]);

        (new CancelEventOccurrence)->handle($occurrence);

        $this->assertSame(EventOccurrenceStatus::Cancelled, $occurrence->refresh()->status);
    }

    public function test_an_occurrence_can_be_skipped()
    {
        $occurrence = EventOccurrence::factory()->create(['status' => EventOccurrenceStatus::Scheduled]);

        (new SkipEventOccurrence)->handle($occurrence);

        $this->assertSame(EventOccurrenceStatus::Skipped, $occurrence->refresh()->status);
    }
}
