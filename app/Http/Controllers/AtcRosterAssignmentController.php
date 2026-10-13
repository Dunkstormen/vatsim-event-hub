<?php

namespace App\Http\Controllers;

use App\Actions\Atc\AssignControllerToPosition;
use App\Http\Requests\Atc\AssignControllerToPositionRequest;
use App\Models\AtcPosition;
use App\Models\AtcRosterAssignment;
use App\Models\EventOccurrence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AtcRosterAssignmentController extends Controller
{
    public function store(AssignControllerToPositionRequest $request, EventOccurrence $eventOccurrence, AtcPosition $atcPosition): RedirectResponse
    {
        $user = User::where('cid', $request->validated('user_cid'))->firstOrFail();

        (new AssignControllerToPosition)->handle(
            $eventOccurrence,
            $atcPosition,
            $user,
            $request->validated('starts_at'),
            $request->validated('ends_at'),
        );

        return back();
    }

    public function destroy(Request $request, AtcRosterAssignment $atcRosterAssignment): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('manage_atc_roster'), 403);

        $atcRosterAssignment->delete();

        return back();
    }
}
