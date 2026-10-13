<?php

namespace App\Actions\Roles;

use App\Models\Fir;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;

class AssignRoleToUser
{
    /**
     * Assign a role to a user, optionally scoped to a FIR. Passing no FIR
     * creates a global assignment that applies across all FIRs.
     *
     * Idempotent: assigning the same role/FIR combination twice returns the
     * existing assignment rather than creating a duplicate.
     */
    public function handle(User $user, Role $role, ?Fir $fir = null): RoleAssignment
    {
        $query = RoleAssignment::query()
            ->where('user_cid', $user->cid)
            ->where('role_id', $role->id);

        $fir === null ? $query->whereNull('fir_id') : $query->where('fir_id', $fir->id);

        return $query->first() ?? RoleAssignment::create([
            'user_cid' => $user->cid,
            'role_id' => $role->id,
            'fir_id' => $fir?->id,
        ]);
    }
}
