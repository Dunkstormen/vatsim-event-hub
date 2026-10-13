<?php

namespace App\Http\Controllers;

use App\Actions\Atc\ExpressInterestInPosition;
use App\Actions\Atc\WithdrawInterestInPosition;
use App\Http\Requests\Atc\ExpressInterestRequest;
use App\Models\AtcPosition;
use App\Models\EventOccurrence;
use Illuminate\Http\RedirectResponse;

class AtcPositionInterestController extends Controller
{
    public function store(ExpressInterestRequest $request, EventOccurrence $eventOccurrence, AtcPosition $atcPosition): RedirectResponse
    {
        (new ExpressInterestInPosition)->handle(
            $request->user(),
            $eventOccurrence,
            $atcPosition,
            $request->validated('availability')
        );

        return back();
    }

    public function destroy(EventOccurrence $eventOccurrence, AtcPosition $atcPosition): RedirectResponse
    {
        abort_unless(request()->user()->hasPermissionTo('book_atc_positions'), 403);

        (new WithdrawInterestInPosition)->handle(request()->user(), $eventOccurrence, $atcPosition);

        return back();
    }
}
