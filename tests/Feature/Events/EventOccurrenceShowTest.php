<?php

namespace Tests\Feature\Events;

use App\Actions\Roles\AssignRoleToUser;
use App\Actions\Roles\GrantPermissionToRole;
use App\Enums\AtcRosterType;
use App\Enums\EventStatus;
use App\Models\AtcPosition;
use App\Models\AtcShift;
use App\Models\Event;
use App\Models\EventAirport;
use App\Models\EventOccurrence;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOccurrenceShowTest extends TestCase
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

    public function test_event_details_page_renders_published_event(): void
    {
        $event = Event::factory()->published()->create([
            'title' => 'Capital Overload',
            'description' => "Our busiest night of the year.\n\nSix airports take part.",
        ]);
        EventAirport::factory()->for($event)->featured()->create(['icao_code' => 'EXAA']);
        EventAirport::factory()->for($event)->create(['icao_code' => 'EXBB']);
        $occurrence = EventOccurrence::factory()->for($event)->create([
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
        ]);

        $this->get("/events/{$occurrence->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/show')
                ->where('event.title', 'Capital Overload')
                ->where('event.airports.0.icao', 'EXAA')
                ->where('event.airports.0.is_featured', true)
                ->where('occurrence.id', $occurrence->id));
    }

    public function test_event_details_page_404s_for_draft_events(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Draft]);
        $occurrence = EventOccurrence::factory()->for($event)->create();

        $this->get("/events/{$occurrence->id}")->assertNotFound();
    }

    public function test_pre_defined_atc_roster_is_visible_to_a_controller(): void
    {
        $event = Event::factory()->published()->create(['atc_roster_type' => AtcRosterType::PreDefined]);
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        $position = AtcPosition::factory()->for($event)->create(['callsign' => 'EXAA_TWR']);
        AtcShift::factory()->for($position, 'position')->create(['label' => 'Early']);

        $this->actingAs($this->controller())
            ->get("/events/{$occurrence->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/show')
                ->where('atcRoster.type', 'pre_defined')
                ->where('atcRoster.visible', true)
                ->where('atcRoster.positions.0.callsign', 'EXAA_TWR')
                ->where('atcRoster.positions.0.shifts.0.label', 'Early'));
    }

    public function test_pre_defined_atc_roster_is_hidden_from_a_guest(): void
    {
        $event = Event::factory()->published()->create(['atc_roster_type' => AtcRosterType::PreDefined]);
        $occurrence = EventOccurrence::factory()->for($event)->create(['starts_at' => now()->addDay()]);
        AtcPosition::factory()->for($event)->create();

        $this->get("/events/{$occurrence->id}")
            ->assertInertia(fn ($page) => $page
                ->component('events/show')
                ->where('atcRoster.visible', false)
                ->where('atcRoster.positions', []));
    }

    public function test_events_without_atc_rostering_have_a_null_roster_payload(): void
    {
        $event = Event::factory()->published()->create(['atc_roster_type' => null]);
        $occurrence = EventOccurrence::factory()->for($event)->create();

        $this->get("/events/{$occurrence->id}")
            ->assertInertia(fn ($page) => $page
                ->component('events/show')
                ->where('atcRoster', null));
    }
}
