<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermissionTo('events.manage');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermissionTo('events.manage');
    }
}
