<?php

use App\Http\Controllers\AtcPositionInterestController;
use App\Http\Controllers\AtcRosterAssignmentController;
use App\Http\Controllers\AtcRosterController;
use App\Http\Controllers\AtcShiftBookingController;
use App\Http\Controllers\EventOccurrenceController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::get('events/{eventOccurrence}', [EventOccurrenceController::class, 'show'])->name('events.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::post('logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    Route::post('occurrences/{eventOccurrence}/atc-positions/{atcPosition}/interest', [AtcPositionInterestController::class, 'store'])
        ->name('atc-positions.interest.store');
    Route::delete('occurrences/{eventOccurrence}/atc-positions/{atcPosition}/interest', [AtcPositionInterestController::class, 'destroy'])
        ->name('atc-positions.interest.destroy');

    Route::post('atc-shifts/{atcShift}/bookings', [AtcShiftBookingController::class, 'store'])
        ->name('atc-shifts.bookings.store');
    Route::delete('atc-shift-bookings/{atcShiftBooking}', [AtcShiftBookingController::class, 'destroy'])
        ->name('atc-shift-bookings.destroy');

    Route::post('occurrences/{eventOccurrence}/atc-positions/{atcPosition}/assignments', [AtcRosterAssignmentController::class, 'store'])
        ->name('atc-roster-assignments.store');
    Route::delete('atc-roster-assignments/{atcRosterAssignment}', [AtcRosterAssignmentController::class, 'destroy'])
        ->name('atc-roster-assignments.destroy');

    Route::post('occurrences/{eventOccurrence}/atc-roster/publish', [AtcRosterController::class, 'publish'])
        ->name('atc-roster.publish');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
