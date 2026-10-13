<?php

namespace App\Http\Controllers;

use App\Enums\AtcRosterType;
use App\Enums\EventStatus;
use App\Models\AtcPosition;
use App\Models\AtcShiftBooking;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EventOccurrenceController extends Controller
{
    public function show(EventOccurrence $eventOccurrence): Response
    {
        $eventOccurrence->loadMissing(['event.airports', 'event.atcPositions.shifts']);
        $event = $eventOccurrence->event;

        abort_unless($event->status === EventStatus::Published, 404);

        $airports = $event->airports
            ->sortByDesc('is_featured')
            ->map(fn ($airport) => [
                'icao' => $airport->icao_code,
                'is_featured' => $airport->is_featured,
            ])
            ->values();

        return Inertia::render('events/show', [
            'occurrence' => [
                'id' => $eventOccurrence->id,
                'status' => $eventOccurrence->status->value,
                'starts_at' => $eventOccurrence->starts_at->toIso8601String(),
                'ends_at' => $eventOccurrence->ends_at->toIso8601String(),
            ],
            'event' => [
                'title' => $event->title,
                'description' => $event->description,
                'recurrence_frequency' => $event->recurrence_frequency?->value,
                'airports' => $airports,
            ],
            'atcRoster' => $this->buildAtcRosterPayload($eventOccurrence),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildAtcRosterPayload(EventOccurrence $occurrence): ?array
    {
        $event = $occurrence->event;

        if ($event->atc_roster_type === null) {
            return null;
        }

        /** @var User|null $user */
        $user = Auth::user();
        $isController = $user !== null && (
            $user->hasPermissionTo('book_atc_positions') || $user->hasPermissionTo('manage_atc_roster')
        );

        if ($event->atc_roster_type === AtcRosterType::PreDefined) {
            return [
                'type' => 'pre_defined',
                'visible' => $isController,
                'positions' => $isController
                    ? $this->preDefinedPositionsPayload($event->atcPositions, $occurrence, $user)
                    : [],
            ];
        }

        $published = $occurrence->isAtcRosterPublished();
        $visible = $isController || $published;

        return [
            'type' => 'open_interest',
            'visible' => $visible,
            'is_published' => $published,
            'interest_window' => [
                'opens_at' => $occurrence->atcInterestOpensAt()?->toIso8601String(),
                'closes_at' => $occurrence->atcInterestClosesAt()?->toIso8601String(),
                'is_open' => $occurrence->isAtcInterestWindowOpen(),
            ],
            'positions' => $visible
                ? $this->openInterestPositionsPayload($event->atcPositions, $occurrence, $user, $isController, $published)
                : [],
        ];
    }

    /**
     * @param  Collection<int, AtcPosition>  $positions
     * @return array<int, array<string, mixed>>
     */
    private function preDefinedPositionsPayload($positions, EventOccurrence $occurrence, ?User $user): array
    {
        $occurrenceShiftBookings = AtcShiftBooking::query()
            ->where('event_occurrence_id', $occurrence->id)
            ->with('user')
            ->get()
            ->keyBy('atc_shift_id');

        return $positions->map(function (AtcPosition $position) use ($occurrenceShiftBookings, $occurrence, $user) {
            return [
                'id' => $position->id,
                'callsign' => $position->callsign,
                'frequency' => $position->frequency,
                'airport_icao' => $position->airport_icao,
                'shifts' => $position->shifts->map(function ($shift) use ($occurrenceShiftBookings, $occurrence, $user) {
                    $booking = $occurrenceShiftBookings->get($shift->id);

                    return [
                        'id' => $shift->id,
                        'label' => $shift->label,
                        'starts_at' => $occurrence->starts_at->copy()->addMinutes($shift->start_offset_minutes)->toIso8601String(),
                        'ends_at' => $occurrence->starts_at->copy()->addMinutes($shift->end_offset_minutes)->toIso8601String(),
                        'booking_id' => $booking?->id,
                        'booked_by_name' => $booking?->user?->name,
                        'booked_by_me' => $user !== null && $booking?->user_cid === $user->cid,
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, AtcPosition>  $positions
     * @return array<int, array<string, mixed>>
     */
    private function openInterestPositionsPayload($positions, EventOccurrence $occurrence, ?User $user, bool $isController, bool $published): array
    {
        $assignments = $published
            ? $occurrence->rosterAssignments()->with('user')->get()->groupBy('atc_position_id')
            : collect();

        $myInterests = $isController && $user !== null
            ? $occurrence->positionInterests()->where('user_cid', $user->cid)->get()->keyBy('atc_position_id')
            : collect();

        return $positions->map(function (AtcPosition $position) use ($assignments, $myInterests) {
            return [
                'id' => $position->id,
                'callsign' => $position->callsign,
                'frequency' => $position->frequency,
                'airport_icao' => $position->airport_icao,
                'my_interest' => $myInterests->get($position->id)?->only(['availability']),
                'assignments' => ($assignments->get($position->id) ?? collect())->map(fn ($assignment) => [
                    'user_name' => $assignment->user?->name,
                    'starts_at' => $assignment->starts_at?->toIso8601String(),
                    'ends_at' => $assignment->ends_at?->toIso8601String(),
                ])->values(),
            ];
        })->values()->all();
    }
}
