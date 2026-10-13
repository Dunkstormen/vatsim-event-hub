<?php

namespace Tests\Feature\Events;

use App\Actions\Events\GenerateEventOccurrences;
use App\Enums\RecurrenceFrequency;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOccurrenceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_recurring_event_gets_a_single_occurrence()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => null,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $this->assertSame(1, $event->occurrences()->count());

        $occurrence = $event->occurrences()->first();
        $this->assertSame('2026-01-05 10:00:00', $occurrence->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-05 12:00:00', $occurrence->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_a_weekly_recurring_event_generates_occurrences_for_six_months()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => RecurrenceFrequency::Weekly,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        // From 2026-01-05 to the 2026-07-05 horizon, stepping weekly, is 26 occurrences.
        $this->assertSame(26, $event->occurrences()->count());

        $first = $event->occurrences()->orderBy('starts_at')->first();
        $last = $event->occurrences()->orderBy('starts_at')->get()->last();

        $this->assertSame('2026-01-05 10:00:00', $first->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-29 10:00:00', $last->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_a_biweekly_recurring_event_steps_two_weeks_at_a_time()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => RecurrenceFrequency::Biweekly,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $starts = $event->occurrences()->orderBy('starts_at')->pluck('starts_at')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->all();

        $this->assertSame('2026-01-05', $starts[0]);
        $this->assertSame('2026-01-19', $starts[1]);
        $this->assertSame('2026-02-02', $starts[2]);
    }

    public function test_a_monthly_recurring_event_generates_seven_occurrences_in_six_months()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => RecurrenceFrequency::Monthly,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $this->assertSame(7, $event->occurrences()->count());
    }

    public function test_a_quarterly_recurring_event_generates_three_occurrences_in_six_months()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => RecurrenceFrequency::Quarterly,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $this->assertSame(3, $event->occurrences()->count());
    }

    public function test_generating_occurrences_twice_does_not_create_duplicates()
    {
        $event = Event::factory()->create([
            'recurrence_frequency' => RecurrenceFrequency::Weekly,
            'starts_at' => '2026-01-05 10:00:00',
            'ends_at' => '2026-01-05 12:00:00',
        ]);

        $countAfterCreate = $event->occurrences()->count();

        (new GenerateEventOccurrences)->handle($event->fresh());

        $this->assertSame($countAfterCreate, $event->occurrences()->count());
    }
}
