<?php

namespace Database\Seeders;

use App\Actions\Roles\GrantPermissionToRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions covering every area of the system an administrator needs
     * to manage. Namespaced as "{domain}.{action}" to match the convention
     * already used by the roles/permissions feature tests.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'events.manage',
        'events.publish',
        'atc.manage',
        'atc.roster.publish',
        'roles.manage',
        'firs.manage',
        'users.manage',
    ];

    /**
     * Seed the "administrator" role with every permission in the system, so
     * it always has full control regardless of which permissions exist.
     */
    public function run(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name) => Permission::query()->firstOrCreate(['name' => $name]));

        $administrator = Role::query()->firstOrCreate(['name' => 'administrator']);

        $permissions->each(fn (Permission $permission) => (new GrantPermissionToRole)->handle($administrator, $permission));
    }
}
