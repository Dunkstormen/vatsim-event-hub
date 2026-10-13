<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtcPosition extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'callsign',
        'frequency',
        'airport_icao',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(AtcShift::class)->orderBy('sort_order');
    }

    public function interests(): HasMany
    {
        return $this->hasMany(AtcPositionInterest::class);
    }

    public function rosterAssignments(): HasMany
    {
        return $this->hasMany(AtcRosterAssignment::class);
    }
}
