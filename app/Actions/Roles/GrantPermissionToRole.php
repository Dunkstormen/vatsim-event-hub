<?php

namespace App\Actions\Roles;

use App\Models\Permission;
use App\Models\Role;

class GrantPermissionToRole
{
    public function handle(Role $role, Permission $permission): void
    {
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
