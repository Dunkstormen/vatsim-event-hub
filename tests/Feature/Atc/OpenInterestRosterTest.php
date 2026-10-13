<?php

namespace Tests\Feature\Atc;

use App\Actions\Roles\AssignRoleToUser;
use App\Actions\Roles\GrantPermissionToRole;
use App\Enums\AtcRosterType;
use App\Models\AtcPosition;
use App\Models\AtcPositionInterest;
use App\Models\AtcRosterAssignment;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenInterestRosterTest extends TestCase
{
    use RefreshDatabase;

    private function controller(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'controller']);
        $permission = Permission::firstOrCreate(['name' => 'book_atc_positions']);
        (new GrantPermissionToRole)->handle($role, $permission);
        (new AssignRoleToUser)->handle($user, $role);

        return $user;
    }

    private function openInterestOccurrence(array $eventOverrides = [], array $occurrenceOverrides = []): EventOccurrence
    {
        $event = Event::factory()->published()->create(array_merge([
            'atc_roster_type' => AtcRosterType::OpenInterest,
            'atc_interest_opens_hours_before' => 7 * 24,
            'atc_interest_closes_hours_before' => 2,
        ], $eventOverrides));

        return EventOccurrence::factory()->for($event)->create(array_merge([
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
        ], $occurrenceOverrides));
    }

    public function test_controller_can_express_interest_while_window_is_open(): void
    {
        $occurrence = $this->openInterestOccurrence();
        $position = AtcPosition::factory()->for($occurrence->event)->create();
        $user = $this->controller();

        $this->actingAs($user)
            ->post("/occurrences/{$occurrence->id}/atc-positions/{$position->id}/interest", [
                'availability' => 'Available all evening',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('atc_position_interests', [
            'atc_position_id' => $position->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
            'availability' => 'Available all evening',
        ]);
    }

    public function test_expressing_interest_fails_outside_the_window(): void
    {
        // The window opens 7 days before start; an occurrence 10 days out
        // hasn't opened its window yet.
        $occurrence = $this->openInterestOccurrence([], [
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(10)->addHours(2),
        ]);
        $position = AtcPosition::factory()->for($occurrence->event)->create();
        $user = $this->controller();

        $this->actingAs($user)
            ->post("/occurrences/{$occurrence->id}/atc-positions/{$position->id}/interest", [
                'availability' => 'Available',
            ])
            ->assertSessionHasErrors('position');

        $this->assertDatabaseMissing('atc_position_interests', [
            'atc_position_id' => $position->id,
            'user_cid' => $user->cid,
        ]);
    }

    public function test_non_controller_cannot_express_interest(): void
    {
        $occurrence = $this->openInterestOccurrence();
        $position = AtcPosition::factory()->for($occurrence->event)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/occurrences/{$occurrence->id}/atc-positions/{$position->id}/interest", [
                'availability' => 'Available',
            ])
            ->assertForbidden();
    }

    public function test_published_roster_is_visible_to_a_guest(): void
    {
        $occurrence = $this->openInterestOccurrence([], ['atc_roster_published_at' => now()]);
        $position = AtcPosition::factory()->for($occurrence->event)->create(['callsign' => 'EKCH_A_TWR']);
        $controller = $this->controller();
        AtcRosterAssignment::factory()->create([
            'atc_position_id' => $position->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $controller->cid,
        ]);

        $this->get("/events/{$occurrence->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('atcRoster.type', 'open_interest')
                ->where('atcRoster.is_published', true)
                ->where('atcRoster.visible', true)
                ->where('atcRoster.positions.0.callsign', 'EKCH_A_TWR')
                ->where('atcRoster.positions.0.assignments.0.user_name', $controller->name));
    }

    public function test_unpublished_roster_is_hidden_from_a_guest(): void
    {
        $occurrence = $this->openInterestOccurrence();
        AtcPosition::factory()->for($occurrence->event)->create();

        $this->get("/events/{$occurrence->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('atcRoster.visible', false)
                ->where('atcRoster.positions', []));
    }

    public function test_withdrawing_interest_fails_after_roster_is_published(): void
    {
        $occurrence = $this->openInterestOccurrence([], ['atc_roster_published_at' => now()]);
        $position = AtcPosition::factory()->for($occurrence->event)->create();
        $user = $this->controller();
        AtcPositionInterest::factory()->create([
            'atc_position_id' => $position->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
        ]);

        $this->actingAs($user)
            ->delete("/occurrences/{$occurrence->id}/atc-positions/{$position->id}/interest")
            ->assertSessionHasErrors('position');
    }
}
