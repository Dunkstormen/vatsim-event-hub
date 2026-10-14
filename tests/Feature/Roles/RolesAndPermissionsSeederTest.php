<?php

namespace Tests\Feature\Roles;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_role_with_every_permission()
    {
        (new RolesAndPermissionsSeeder)->run();

        $administrator = Role::query()->where('name', 'administrator')->firstOrFail();

        $this->assertSame(
            count(RolesAndPermissionsSeeder::PERMISSIONS),
            Permission::query()->count()
        );

        foreach (RolesAndPermissionsSeeder::PERMISSIONS as $permission) {
            $this->assertTrue($administrator->hasPermissionTo($permission));
        }
    }

    public function test_it_is_idempotent_when_run_more_than_once()
    {
        (new RolesAndPermissionsSeeder)->run();
        (new RolesAndPermissionsSeeder)->run();

        $this->assertSame(1, Role::query()->where('name', 'administrator')->count());
        $this->assertSame(count(RolesAndPermissionsSeeder::PERMISSIONS), Permission::query()->count());
    }
}
