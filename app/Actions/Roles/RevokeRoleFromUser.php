<?php

namespace App\Actions\Roles;

use App\Models\Fir;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;

class RevokeRoleFromUser
{
    /**
     * Revoke a user's role assignment, optionally scoped to a FIR. Passing
     * no FIR revokes the global assignment, leaving any FIR-scoped
     * assignments of the same role untouched.
     */
    public function handle(User $user, Role $role, ?Fir $fir = null): void
    {
        $query = RoleAssignment::query()
            ->where('user_cid', $user->cid)
            ->where('role_id', $role->id);

        $fir === null ? $query->whereNull('fir_id') : $query->where('fir_id', $fir->id);

        $query->delete();
    }
}
