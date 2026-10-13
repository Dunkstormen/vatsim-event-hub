<?php

namespace Tests\Feature\Atc;

use App\Actions\Roles\AssignRoleToUser;
use App\Actions\Roles\GrantPermissionToRole;
use App\Enums\AtcRosterType;
use App\Models\AtcPosition;
use App\Models\AtcShift;
use App\Models\AtcShiftBooking;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreDefinedShiftBookingTest extends TestCase
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

    private function preDefinedEvent(): Event
    {
        return Event::factory()->published()->create([
            'atc_roster_type' => AtcRosterType::PreDefined,
        ]);
    }

    public function test_controller_can_book_a_shift_on_the_next_upcoming_occurrence(): void
    {
        $event = $this->preDefinedEvent();
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $position = AtcPosition::factory()->for($event)->create();
        $shift = AtcShift::factory()->for($position, 'position')->create([
            'start_offset_minutes' => 0,
            'end_offset_minutes' => 120,
        ]);
        $user = $this->controller();

        $this->actingAs($user)
            ->post("/atc-shifts/{$shift->id}/bookings")
            ->assertRedirect();

        $this->assertDatabaseHas('atc_shift_bookings', [
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
        ]);
    }

    public function test_booking_fails_for_overlapping_shifts_on_the_same_occurrence(): void
    {
        $event = $this->preDefinedEvent();
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $positionA = AtcPosition::factory()->for($event)->create();
        $positionB = AtcPosition::factory()->for($event)->create();
        $shiftA = AtcShift::factory()->for($positionA, 'position')->create([
            'start_offset_minutes' => 0,
            'end_offset_minutes' => 120,
        ]);
        $shiftB = AtcShift::factory()->for($positionB, 'position')->create([
            'start_offset_minutes' => 60,
            'end_offset_minutes' => 180,
        ]);
        $user = $this->controller();

        AtcShiftBooking::create([
            'atc_shift_id' => $shiftA->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
        ]);

        $this->actingAs($user)
            ->post("/atc-shifts/{$shiftB->id}/bookings")
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseMissing('atc_shift_bookings', [
            'atc_shift_id' => $shiftB->id,
        ]);
    }

    public function test_booking_only_targets_the_next_upcoming_occurrence_not_a_later_one(): void
    {
        $event = $this->preDefinedEvent();
        EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $laterOccurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addWeek()]);
        $position = AtcPosition::factory()->for($event)->create();
        $shift = AtcShift::factory()->for($position, 'position')->create();
        $user = $this->controller();

        $this->actingAs($user)->post("/atc-shifts/{$shift->id}/bookings");

        $this->assertDatabaseMissing('atc_shift_bookings', [
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $laterOccurrence->id,
        ]);
    }

    public function test_no_booking_is_created_when_the_event_has_no_upcoming_occurrence(): void
    {
        $event = Event::factory()->published()->create([
            'atc_roster_type' => AtcRosterType::PreDefined,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHours(2),
        ]);
        $position = AtcPosition::factory()->for($event)->create();
        $shift = AtcShift::factory()->for($position, 'position')->create();
        $user = $this->controller();

        $this->actingAs($user)
            ->post("/atc-shifts/{$shift->id}/bookings")
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('atc_shift_bookings', 0);
    }

    public function test_controller_can_release_their_own_booking(): void
    {
        $event = $this->preDefinedEvent();
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $position = AtcPosition::factory()->for($event)->create();
        $shift = AtcShift::factory()->for($position, 'position')->create();
        $user = $this->controller();
        $booking = AtcShiftBooking::create([
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $user->cid,
        ]);

        $this->actingAs($user)
            ->delete("/atc-shift-bookings/{$booking->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('atc_shift_bookings', ['id' => $booking->id]);
    }

    public function test_controller_cannot_release_someone_elses_booking(): void
    {
        $event = $this->preDefinedEvent();
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $position = AtcPosition::factory()->for($event)->create();
        $shift = AtcShift::factory()->for($position, 'position')->create();
        $owner = $this->controller();
        $otherController = $this->controller();
        $booking = AtcShiftBooking::create([
            'atc_shift_id' => $shift->id,
            'event_occurrence_id' => $occurrence->id,
            'user_cid' => $owner->cid,
        ]);

        $this->actingAs($otherController)
            ->delete("/atc-shift-bookings/{$booking->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('atc_shift_bookings', ['id' => $booking->id]);
    }
}
