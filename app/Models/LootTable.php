<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named loot pool shared by many markers (e.g. every "Tier 2 box" on a map).
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int|null $source_id
 * @property array<string, mixed>|null $metadata
 */
#[Fillable(['key', 'name', 'source_id', 'metadata'])]
class LootTable extends Model
{
    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /**
     * @return BelongsToMany<Item, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->withPivot('chance')->withTimestamps();
    }

    /**
     * @return HasMany<Marker, $this>
     */
    public function markers(): HasMany
    {
        return $this->hasMany(Marker::class);
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
