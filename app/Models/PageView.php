<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $visitor_hash
 * @property string $path
 * @property string|null $referrer_host
 * @property string $device
 */
#[Fillable(['visitor_hash', 'path', 'referrer_host', 'device'])]
class PageView extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const RETENTION_DAYS = 90;

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
