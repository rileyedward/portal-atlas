<?php

namespace App\Policies;

use App\Models\RaidRoute;
use App\Models\User;

class RaidRoutePolicy
{
    public function update(User $user, RaidRoute $route): bool
    {
        return $route->user_id === $user->id;
    }

    public function delete(User $user, RaidRoute $route): bool
    {
        return $route->user_id === $user->id;
    }
}
