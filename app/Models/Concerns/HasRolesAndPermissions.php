<?php

namespace App\Models\Concerns;

use App\Models\Fir;
use App\Models\RoleAssignment;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRolesAndPermissions
{
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class, 'user_cid', 'cid');
    }

    /**
     * Determine whether the user has the given role, either globally or
     * scoped to the given FIR. A global role assignment (no FIR attached)
     * always applies, regardless of which FIR is being checked.
     */
    public function hasRole(string $role, ?Fir $fir = null): bool
    {
        return $this->roleAssignments()
            ->applicableToFir($fir)
            ->whereHas('role', fn ($query) => $query->where('name', $role))
            ->exists();
    }

    /**
     * Determine whether the user has the given permission, either via a
     * global role or a role scoped to the given FIR.
     */
    public function hasPermissionTo(string $permission, ?Fir $fir = null): bool
    {
        return $this->roleAssignments()
            ->applicableToFir($fir)
            ->whereHas(
                'role',
                fn ($query) => $query->whereHas(
                    'permissions',
                    fn ($query) => $query->where('name', $permission)
                )
            )
            ->exists();
    }
}
