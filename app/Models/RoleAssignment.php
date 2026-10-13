<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    use HasFactory;

    protected $table = 'role_user';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_cid',
        'role_id',
        'fir_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_cid', 'cid');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function fir(): BelongsTo
    {
        return $this->belongsTo(Fir::class);
    }

    /**
     * Scope to assignments that are global, i.e. not scoped to any FIR.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('fir_id');
    }

    /**
     * Scope to assignments that apply to the given FIR: global assignments
     * plus assignments scoped specifically to that FIR.
     */
    public function scopeApplicableToFir(Builder $query, ?Fir $fir): Builder
    {
        return $query->where(function (Builder $query) use ($fir): void {
            $query->whereNull('fir_id');

            if ($fir !== null) {
                $query->orWhere('fir_id', $fir->id);
            }
        });
    }
}
