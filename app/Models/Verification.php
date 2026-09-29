<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A user (or admin) confirming that a data point is accurate for a game version.
 *
 * @property int $id
 * @property string $verifiable_type
 * @property int $verifiable_id
 * @property int $user_id
 * @property int|null $game_version_id
 * @property bool $is_admin
 * @property string|null $note
 */
#[Fillable(['verifiable_type', 'verifiable_id', 'user_id', 'game_version_id', 'is_admin', 'note'])]
class Verification extends Model
{
    protected function casts(): array
    {
        return ['is_admin' => 'boolean'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
