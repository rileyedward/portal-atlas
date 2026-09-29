<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Typed access to pivot attributes on models loaded through a BelongsToMany relation.
 */
final class Pivot
{
    public static function get(Model $model, string $key): mixed
    {
        $pivot = $model->relationLoaded('pivot') ? $model->getRelation('pivot') : null;

        return $pivot instanceof Model ? $pivot->getAttribute($key) : null;
    }

    public static function int(Model $model, string $key): int
    {
        $value = self::get($model, $key);

        return is_numeric($value) ? (int) $value : 0;
    }

    public static function string(Model $model, string $key): ?string
    {
        $value = self::get($model, $key);

        return is_scalar($value) ? (string) $value : null;
    }
}
