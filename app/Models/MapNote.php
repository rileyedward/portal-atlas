<?php

namespace App\Models;

use Database\Factories\MapNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private, per-user annotation on a map.
 *
 * @property int $id
 * @property int $user_id
 * @property int $map_id
 * @property float $x
 * @property float $y
 * @property string $title
 * @property string|null $body
 * @property string|null $color
 * @property bool $is_shared
 * @property string|null $share_token
 */
#[Fillable(['map_id', 'x', 'y', 'title', 'body', 'color', 'is_shared', 'share_token'])]
class MapNote extends Model
{
    /** @use HasFactory<MapNoteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Map, $this>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
}
