<?php

namespace App\Models;

use App\Enums\AtcRosterType;
use App\Enums\EventOccurrenceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EventOccurrence extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'starts_at',
        'ends_at',
        'status',
        'atc_roster_published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventOccurrenceStatus::class,
            'atc_roster_published_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function positionInterests(): HasMany
    {
        return $this->hasMany(AtcPositionInterest::class);
    }

    public function rosterAssignments(): HasMany
    {
        return $this->hasMany(AtcRosterAssignment::class);
    }

    public function shiftBookings(): HasMany
    {
        return $this->hasMany(AtcShiftBooking::class);
    }

    public function isAtcRosterPublished(): bool
    {
        return $this->atc_roster_published_at !== null;
    }

    /**
     * Serialized shape used by the homepage and event index event cards.
     * Expects `event.airports` to already be eager loaded.
     *
     * @return array{id: int, title: string, description: ?string, starts_at: string, ends_at: string, airports: list<string>}
     */
    public function toEventCardArray(): array
    {
        $airports = $this->event->airports
            ->sortByDesc('is_featured')
            ->pluck('icao_code')
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'title' => $this->event->title,
            'description' => $this->event->description,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'airports' => $airports,
        ];
    }

    /**
     * Whether the open-interest window is currently open for this
     * occurrence, based on the owning event's configured offsets.
     */
    public function isAtcInterestWindowOpen(): bool
    {
        if ($this->event->atc_roster_type !== AtcRosterType::OpenInterest) {
            return false;
        }

        $opensAt = $this->atcInterestOpensAt();
        $closesAt = $this->atcInterestClosesAt();

        if ($opensAt === null || $closesAt === null) {
            return false;
        }

        return now()->between($opensAt, $closesAt);
    }

    public function atcInterestOpensAt(): ?Carbon
    {
        if ($this->event->atc_interest_opens_hours_before === null) {
            return null;
        }

        return $this->starts_at->copy()->subHours($this->event->atc_interest_opens_hours_before);
    }

    public function atcInterestClosesAt(): ?Carbon
    {
        if ($this->event->atc_interest_closes_hours_before === null) {
            return null;
        }

        return $this->starts_at->copy()->subHours($this->event->atc_interest_closes_hours_before);
    }
}
