<?php

namespace App\Models;

use Database\Factories\RaidRouteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $map_id
 * @property string $name
 * @property string|null $description
 * @property list<array{x: float, y: float, marker_id?: int|null, label?: string|null}> $points
 * @property bool $is_public
 * @property string|null $share_token
 * @property-read Map $map
 * @property-read User $user
 */
#[Fillable(['map_id', 'name', 'description', 'points', 'is_public', 'share_token'])]
class RaidRoute extends Model
{
    /** @use HasFactory<RaidRouteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'array',
            'is_public' => 'boolean',
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
