<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class VatsimAuthController extends Controller
{
    /**
     * Redirect the user to the VATSIM Connect authorization page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('vatsim')->redirect();
    }

    /**
     * Handle the VATSIM Connect callback, creating/updating the user and logging them in.
     */
    public function callback(): RedirectResponse
    {
        $vatsimUser = Socialite::driver('vatsim')->user();

        $user = User::updateOrCreate(
            ['cid' => $vatsimUser->getId()],
            [
                'name' => $vatsimUser->getName(),
                'email' => $vatsimUser->getEmail(),
                'rating' => $vatsimUser->rating,
                'area' => $vatsimUser->area,
                'division' => $vatsimUser->division,
                'subdivision' => $vatsimUser->subdivision,
            ]
        );

        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }
}
