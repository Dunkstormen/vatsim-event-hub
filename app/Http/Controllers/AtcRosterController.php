<?php

namespace App\Http\Controllers;

use App\Actions\Atc\PublishAtcRoster;
use App\Models\EventOccurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AtcRosterController extends Controller
{
    public function publish(Request $request, EventOccurrence $eventOccurrence): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('manage_atc_roster'), 403);

        (new PublishAtcRoster)->handle($eventOccurrence);

        return back();
    }
}
