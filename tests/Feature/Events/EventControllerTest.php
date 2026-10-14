<?php

namespace Tests\Feature\Events;

use App\Actions\Roles\AssignRoleToUser;
use App\Actions\Roles\GrantPermissionToRole;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    private function eventManager(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'events-manager']);
        $permission = Permission::firstOrCreate(['name' => 'events.manage']);
        (new GrantPermissionToRole)->handle($role, $permission);
        (new AssignRoleToUser)->handle($user, $role);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Capital Overload',
            'description' => 'A fly-in event.',
            'status' => EventStatus::Published->value,
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addWeek()->addHours(2)->format('Y-m-d\TH:i'),
            'recurrence_frequency' => null,
        ], $overrides);
    }

    public function test_guest_cannot_create_events(): void
    {
        $this->get(route('events.create'))->assertRedirect('/');

        $this->post(route('events.store'), $this->validPayload())
            ->assertRedirect('/');
    }

    public function test_user_without_permission_cannot_create_events(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('events.create'))->assertForbidden();
        $this->actingAs($user)->post(route('events.store'), $this->validPayload())->assertForbidden();
    }

    public function test_event_manager_can_create_a_non_recurring_event(): void
    {
        $user = $this->eventManager();

        $this->actingAs($user)
            ->post(route('events.store'), $this->validPayload(['title' => 'New Event']))
            ->assertRedirect(route('events.index'));

        $event = Event::query()->where('title', 'New Event')->firstOrFail();
        $this->assertSame(1, $event->occurrences()->count());
    }

    public function test_event_manager_can_create_a_recurring_event_and_occurrences_are_generated(): void
    {
        $user = $this->eventManager();

        $this->actingAs($user)->post(route('events.store'), $this->validPayload([
            'title' => 'Weekly Fly-in',
            'recurrence_frequency' => 'weekly',
        ]))->assertRedirect(route('events.index'));

        $event = Event::query()->where('title', 'Weekly Fly-in')->firstOrFail();
        $this->assertGreaterThan(1, $event->occurrences()->count());
    }

    public function test_update_validation_rejects_ends_at_before_starts_at(): void
    {
        $user = $this->eventManager();
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->put(route('events.update', $event), $this->validPayload([
                'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'ends_at' => now()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_event_manager_can_upload_a_banner(): void
    {
        Storage::fake('public');
        $user = $this->eventManager();

        $this->actingAs($user)->post(route('events.store'), array_merge($this->validPayload(), [
            'banner' => UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg'),
        ]))->assertRedirect(route('events.index'));

        $event = Event::query()->where('title', 'Capital Overload')->firstOrFail();
        $this->assertNotNull($event->banner_path);
        Storage::disk('public')->assertExists($event->banner_path);
    }

    public function test_oversized_banner_upload_is_rejected_with_a_clear_error(): void
    {
        Storage::fake('public');
        $user = $this->eventManager();

        $this->actingAs($user)->post(route('events.store'), array_merge($this->validPayload(), [
            'banner' => UploadedFile::fake()->create('banner.jpg', 6000, 'image/jpeg'),
        ]))->assertSessionHasErrors('banner');

        $this->assertDatabaseMissing('events', ['title' => 'Capital Overload']);
    }

    public function test_event_manager_can_delete_an_event(): void
    {
        $user = $this->eventManager();
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_user_without_permission_cannot_delete_an_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->delete(route('events.destroy', $event))->assertForbidden();
    }
}
