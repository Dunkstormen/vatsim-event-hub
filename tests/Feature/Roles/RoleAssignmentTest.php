<?php

namespace Tests\Feature\Roles;

use App\Actions\Roles\AssignRoleToUser;
use App\Actions\Roles\GrantPermissionToRole;
use App\Actions\Roles\RevokeRoleFromUser;
use App\Models\Fir;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_global_role_assignment_applies_across_all_firs()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'event_manager']);
        $egtt = Fir::factory()->create(['code' => 'EGTT']);
        $lfff = Fir::factory()->create(['code' => 'LFFF']);

        (new AssignRoleToUser)->handle($user, $role);

        $this->assertTrue($user->hasRole('event_manager'));
        $this->assertTrue($user->hasRole('event_manager', $egtt));
        $this->assertTrue($user->hasRole('event_manager', $lfff));
    }

    public function test_a_fir_scoped_role_assignment_only_applies_within_that_fir()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'fir_controller']);
        $egtt = Fir::factory()->create(['code' => 'EGTT']);
        $lfff = Fir::factory()->create(['code' => 'LFFF']);

        (new AssignRoleToUser)->handle($user, $role, $egtt);

        $this->assertFalse($user->hasRole('fir_controller'));
        $this->assertTrue($user->hasRole('fir_controller', $egtt));
        $this->assertFalse($user->hasRole('fir_controller', $lfff));
    }

    public function test_assigning_the_same_role_and_fir_twice_does_not_create_a_duplicate()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $fir = Fir::factory()->create();

        $first = (new AssignRoleToUser)->handle($user, $role, $fir);
        $second = (new AssignRoleToUser)->handle($user, $role, $fir);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $user->roleAssignments()->count());
    }

    public function test_revoking_a_role_removes_only_the_matching_scope()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $egtt = Fir::factory()->create();

        (new AssignRoleToUser)->handle($user, $role);
        (new AssignRoleToUser)->handle($user, $role, $egtt);

        (new RevokeRoleFromUser)->handle($user, $role, $egtt);

        $this->assertTrue($user->hasRole($role->name));
        $this->assertTrue($user->hasRole($role->name, $egtt));
        $this->assertSame(1, $user->roleAssignments()->count());
    }

    public function test_a_user_inherits_permissions_from_roles_applicable_to_a_fir()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'fir_controller']);
        $permission = Permission::factory()->create(['name' => 'events.publish']);
        $egtt = Fir::factory()->create();
        $lfff = Fir::factory()->create();

        (new GrantPermissionToRole)->handle($role, $permission);
        (new AssignRoleToUser)->handle($user, $role, $egtt);

        $this->assertTrue($user->hasPermissionTo('events.publish', $egtt));
        $this->assertFalse($user->hasPermissionTo('events.publish', $lfff));
        $this->assertFalse($user->hasPermissionTo('events.publish'));
    }

    public function test_a_global_role_grants_permissions_for_every_fir()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $fir = Fir::factory()->create();

        (new GrantPermissionToRole)->handle($role, $permission);
        (new AssignRoleToUser)->handle($user, $role);

        $this->assertTrue($user->hasPermissionTo($permission->name));
        $this->assertTrue($user->hasPermissionTo($permission->name, $fir));
    }
}
