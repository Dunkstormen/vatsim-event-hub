<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtcShift extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'atc_position_id',
        'label',
        'start_offset_minutes',
        'end_offset_minutes',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_offset_minutes' => 'integer',
            'end_offset_minutes' => 'integer',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(AtcPosition::class, 'atc_position_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(AtcShiftBooking::class);
    }

    /**
     * Whether this shift's [start, end) offset range overlaps another
     * shift's offset range. Used to prevent a controller from booking two
     * overlapping positions within the same occurrence.
     */
    public function overlaps(self $other): bool
    {
        return $this->start_offset_minutes < $other->end_offset_minutes
            && $this->end_offset_minutes > $other->start_offset_minutes;
    }
}
