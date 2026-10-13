<?php

namespace App\Actions\Atc;

use App\Models\AtcPosition;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class WithdrawInterestInPosition
{
    /**
     * Withdraw a controller's own expression of interest. Allowed any time
     * before the roster for that occurrence has been published.
     */
    public function handle(User $user, EventOccurrence $occurrence, AtcPosition $position): void
    {
        if ($occurrence->isAtcRosterPublished()) {
            throw ValidationException::withMessages([
                'position' => 'The roster has already been published for this occurrence.',
            ]);
        }

        $occurrence->positionInterests()
            ->where('atc_position_id', $position->id)
            ->where('user_cid', $user->cid)
            ->delete();
    }
}
