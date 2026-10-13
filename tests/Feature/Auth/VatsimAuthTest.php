<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class VatsimAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeVatsimUser(array $overrides = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => 1234567,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'rating' => 2,
            'area' => 'EUR',
            'division' => 'EUD',
            'subdivision' => 'FRA',
        ], $overrides);

        return (new SocialiteUser)->setRaw($attributes)->map($attributes);
    }

    public function test_redirect_sends_the_user_to_vatsim_connect()
    {
        $response = $this->get(route('auth.vatsim.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('vatsim', $response->headers->get('Location'));
    }

    public function test_callback_creates_a_new_user_and_logs_them_in()
    {
        Socialite::fake('vatsim', $this->fakeVatsimUser());

        $this->assertDatabaseMissing('users', ['cid' => 1234567]);

        $response = $this->get(route('auth.vatsim.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'cid' => 1234567,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'rating' => 2,
            'area' => 'EUR',
            'division' => 'EUD',
            'subdivision' => 'FRA',
        ]);
    }

    public function test_callback_updates_an_existing_user_by_cid()
    {
        $existing = User::factory()->create([
            'cid' => 1234567,
            'name' => 'Old Name',
            'rating' => 1,
        ]);

        Socialite::fake('vatsim', $this->fakeVatsimUser(['name' => 'New Name', 'rating' => 3]));

        $this->get(route('auth.vatsim.callback'));

        $this->assertAuthenticatedAs($existing->fresh());

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'cid' => 1234567,
            'name' => 'New Name',
            'rating' => 3,
        ]);
    }

    public function test_authenticated_users_cannot_access_the_vatsim_auth_routes()
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('auth.vatsim.redirect'))->assertRedirect(route('dashboard'));
        $this->get(route('auth.vatsim.callback'))->assertRedirect(route('dashboard'));
    }
}
