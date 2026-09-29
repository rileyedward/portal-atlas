<?php

namespace App\Policies;

use App\Enums\MapStatus;
use App\Models\Map;
use App\Models\User;

class MapPolicy
{
    public function view(?User $user, Map $map): bool
    {
        return $map->status === MapStatus::Published || (bool) $user?->canManageContent();
    }
}
