<?php

namespace App\Http\Controllers;

use App\Actions\Atc\BookAtcShift;
use App\Actions\Atc\ReleaseAtcShiftBooking;
use App\Models\AtcShift;
use App\Models\AtcShiftBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AtcShiftBookingController extends Controller
{
    public function store(Request $request, AtcShift $atcShift): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('book_atc_positions'), 403);

        (new BookAtcShift)->handle($request->user(), $atcShift);

        return back();
    }

    public function destroy(Request $request, AtcShiftBooking $atcShiftBooking): RedirectResponse
    {
        $isOwnBooking = $atcShiftBooking->user_cid === $request->user()->cid;
        $canManageRoster = $request->user()->hasPermissionTo('manage_atc_roster');

        abort_unless($isOwnBooking || $canManageRoster, 403);

        (new ReleaseAtcShiftBooking)->handle($atcShiftBooking);

        return back();
    }
}
