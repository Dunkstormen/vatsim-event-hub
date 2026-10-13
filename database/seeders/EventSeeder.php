<?php

namespace Database\Seeders;

use App\Enums\AtcRosterType;
use App\Enums\EventStatus;
use App\Enums\RecurrenceFrequency;
use App\Models\AtcPosition;
use App\Models\AtcPositionInterest;
use App\Models\AtcRosterAssignment;
use App\Models\AtcShiftBooking;
use App\Models\Event;
use App\Models\EventAirport;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds staging with a believable mix of recurring and one-off events,
 * covering both ATC roster types (pre-defined shift booking and
 * open-interest) so the full event lifecycle can be reviewed end to end.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $controllers = User::factory()->count(8)->create();

        $this->seedWeeklyPreDefinedEvent($controllers);
        $this->seedMonthlyOpenInterestEvent($controllers);
        $this->seedBiweeklyUnstaffedEvent();
        $this->seedOneOffPreDefinedEvent($controllers);
        $this->seedOneOffOpenInterestWindowOpenEvent($controllers);
    }

    private function seedWeeklyPreDefinedEvent($controllers): void
    {
        $event = Event::create([
            'title' => 'EGLL Friday Night Ops',
            'description' => 'Weekly Friday evening operations into and out of London Heathrow. Pre-defined ATC shifts cover the full event window.',
            'status' => EventStatus::Published,
            'starts_at' => now()->next('Friday')->setTime(19, 0),
            'ends_at' => now()->next('Friday')->setTime(21, 0),
            'recurrence_frequency' => RecurrenceFrequency::Weekly,
            'atc_roster_type' => AtcRosterType::PreDefined,
        ]);

        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'EGLL', 'is_featured' => true]);
        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'EGKK', 'is_featured' => false]);

        $positions = collect([
            ['callsign' => 'EGLL_DEL', 'frequency' => '121.975'],
            ['callsign' => 'EGLL_GND', 'frequency' => '121.900'],
            ['callsign' => 'EGLL_TWR', 'frequency' => '118.700'],
            ['callsign' => 'EGLL_APP', 'frequency' => '119.725'],
        ])->map(fn ($attrs, $i) => AtcPosition::create([
            'event_id' => $event->id,
            'callsign' => $attrs['callsign'],
            'frequency' => $attrs['frequency'],
            'airport_icao' => 'EGLL',
            'sort_order' => $i,
        ]));

        $shiftsByPosition = $positions->map(function (AtcPosition $position) {
            return collect([
                $position->shifts()->create(['label' => 'First hour', 'start_offset_minutes' => 0, 'end_offset_minutes' => 60, 'sort_order' => 0]),
                $position->shifts()->create(['label' => 'Second hour', 'start_offset_minutes' => 60, 'end_offset_minutes' => 120, 'sort_order' => 1]),
            ]);
        });

        $occurrence = $event->nextUpcomingOccurrence();

        $shiftsByPosition->flatten()->take(5)->each(function ($shift) use ($occurrence, $controllers) {
            AtcShiftBooking::create([
                'atc_shift_id' => $shift->id,
                'event_occurrence_id' => $occurrence->id,
                'user_cid' => $controllers->random()->cid,
            ]);
        });
    }

    private function seedMonthlyOpenInterestEvent($controllers): void
    {
        $event = Event::create([
            'title' => 'Cross the Pond Westbound',
            'description' => 'Monthly transatlantic group flight from Europe to North America. Controllers register interest and are rostered onto a position ahead of the event.',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(10)->setTime(18, 0),
            'ends_at' => now()->addDays(10)->setTime(22, 0),
            'recurrence_frequency' => RecurrenceFrequency::Monthly,
            'atc_roster_type' => AtcRosterType::OpenInterest,
            'atc_interest_opens_hours_before' => 24 * 14,
            'atc_interest_closes_hours_before' => 24,
        ]);

        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'EINN', 'is_featured' => true]);
        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'KJFK', 'is_featured' => true]);
        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'EGGW', 'is_featured' => false]);

        $positions = collect([
            ['callsign' => 'EINN_CTR', 'frequency' => '134.750'],
            ['callsign' => 'GANDER_CTR', 'frequency' => '127.900'],
            ['callsign' => 'NEW_YORK_CTR', 'frequency' => '135.350'],
        ])->map(fn ($attrs, $i) => AtcPosition::create([
            'event_id' => $event->id,
            'callsign' => $attrs['callsign'],
            'frequency' => $attrs['frequency'],
            'airport_icao' => null,
            'sort_order' => $i,
        ]));

        $occurrence = $event->nextUpcomingOccurrence();
        $occurrence->update(['atc_roster_published_at' => now()]);

        $positions->each(function (AtcPosition $position) use ($occurrence, $controllers) {
            $user = $controllers->random();

            AtcPositionInterest::create([
                'atc_position_id' => $position->id,
                'event_occurrence_id' => $occurrence->id,
                'user_cid' => $user->cid,
                'availability' => 'Full event',
            ]);

            AtcRosterAssignment::create([
                'atc_position_id' => $position->id,
                'event_occurrence_id' => $occurrence->id,
                'user_cid' => $user->cid,
                'starts_at' => $occurrence->starts_at,
                'ends_at' => $occurrence->ends_at,
            ]);
        });

        // A later occurrence still has its interest window open, with one
        // registered controller and no roster published yet.
        $laterOccurrence = $event->occurrences()
            ->where('starts_at', '>', $occurrence->starts_at)
            ->orderBy('starts_at')
            ->first();

        if ($laterOccurrence !== null) {
            AtcPositionInterest::create([
                'atc_position_id' => $positions->first()->id,
                'event_occurrence_id' => $laterOccurrence->id,
                'user_cid' => $controllers->random()->cid,
                'availability' => 'First two hours only',
            ]);
        }
    }

    private function seedBiweeklyUnstaffedEvent(): void
    {
        $event = Event::create([
            'title' => 'Beginner Pilot Meetup',
            'description' => 'Casual biweekly meetup for new VATSIM pilots to practice procedures with no formal ATC roster.',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(3)->setTime(17, 0),
            'ends_at' => now()->addDays(3)->setTime(18, 30),
            'recurrence_frequency' => RecurrenceFrequency::Biweekly,
        ]);

        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'KSAN', 'is_featured' => true]);
    }

    private function seedOneOffPreDefinedEvent($controllers): void
    {
        $event = Event::create([
            'title' => 'EKCH Spring Fly-in',
            'description' => 'One-off fly-in at Copenhagen Kastrup with a fully pre-defined ATC roster.',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(5)->setTime(16, 0),
            'ends_at' => now()->addDays(5)->setTime(19, 0),
            'atc_roster_type' => AtcRosterType::PreDefined,
        ]);

        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'EKCH', 'is_featured' => true]);

        $position = AtcPosition::create([
            'event_id' => $event->id,
            'callsign' => 'EKCH_TWR',
            'frequency' => '118.100',
            'airport_icao' => 'EKCH',
            'sort_order' => 0,
        ]);

        $shift = $position->shifts()->create([
            'label' => 'Full event',
            'start_offset_minutes' => 0,
            'end_offset_minutes' => 180,
            'sort_order' => 0,
        ]);

        $occurrence = $event->nextUpcomingOccurrence();

        AtcShiftBooking::create([
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $controllers->random()->cid,
        ]);
    }

    private function seedOneOffOpenInterestWindowOpenEvent($controllers): void
    {
        $event = Event::create([
            'title' => 'Nordic FIR Takeover',
            'description' => 'Nordic vACCs take over a busy evening of traffic. Interest window is currently open for controllers to register.',
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(20)->setTime(18, 0),
            'ends_at' => now()->addDays(20)->setTime(21, 0),
            'atc_roster_type' => AtcRosterType::OpenInterest,
            'atc_interest_opens_hours_before' => 24 * 30,
            'atc_interest_closes_hours_before' => 2,
        ]);

        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'ENGM', 'is_featured' => true]);
        EventAirport::create(['event_id' => $event->id, 'icao_code' => 'ESSA', 'is_featured' => true]);

        $position = AtcPosition::create([
            'event_id' => $event->id,
            'callsign' => 'ENGM_APP',
            'frequency' => '119.100',
            'airport_icao' => 'ENGM',
            'sort_order' => 0,
        ]);

        $occurrence = $event->nextUpcomingOccurrence();

        AtcPositionInterest::create([
            'atc_position_id' => $position->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $controllers->random()->cid,
            'availability' => 'Full event',
        ]);
    }
}
