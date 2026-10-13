<?php

namespace App\Models;

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
}
