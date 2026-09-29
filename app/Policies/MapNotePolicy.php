<?php

namespace App\Policies;

use App\Models\MapNote;
use App\Models\User;

class MapNotePolicy
{
    public function update(User $user, MapNote $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, MapNote $note): bool
    {
        return $note->user_id === $user->id;
    }
}
