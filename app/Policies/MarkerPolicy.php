<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\MapStatus;
use App\Models\Marker;
use App\Models\User;

class MarkerPolicy
{
    public function view(?User $user, Marker $marker): bool
    {
        if ($user?->canManageContent()) {
            return true;
        }

        return $marker->status === ContentStatus::Published
            && $marker->is_visible
            && $marker->map->status === MapStatus::Published;
    }
}
