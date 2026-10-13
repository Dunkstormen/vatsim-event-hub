<?php

namespace App\Models;

use App\Enums\AtcRosterType;
use App\Enums\EventOccurrenceStatus;
use App\Enums\EventStatus;
use App\Enums\RecurrenceFrequency;
use App\Observers\EventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(EventObserver::class)]
class Event extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'banner_path',
        'recurrence_frequency',
        'atc_roster_type',
        'atc_interest_opens_hours_before',
        'atc_interest_closes_hours_before',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'recurrence_frequency' => RecurrenceFrequency::class,
            'atc_roster_type' => AtcRosterType::class,
            'atc_interest_opens_hours_before' => 'integer',
            'atc_interest_closes_hours_before' => 'integer',
        ];
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_frequency !== null;
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    public function airports(): HasMany
    {
        return $this->hasMany(EventAirport::class);
    }

    public function featuredAirports(): HasMany
    {
        return $this->airports()->where('is_featured', true);
    }

    public function participatingAirports(): HasMany
    {
        return $this->airports()->where('is_featured', false);
    }

    public function atcPositions(): HasMany
    {
        return $this->hasMany(AtcPosition::class)->orderBy('sort_order');
    }

    /**
     * The next occurrence of this event that has not started yet. Pre-defined
     * ATC shifts may only be booked against this occurrence: a booking does
     * not carry over to later occurrences of a recurring event.
     */
    public function nextUpcomingOccurrence(): ?EventOccurrence
    {
        return $this->occurrences()
            ->where('status', EventOccurrenceStatus::Scheduled)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->first();
    }
}
