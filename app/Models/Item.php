<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasDataQuality;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $item_category_id
 * @property string $slug
 * @property string|null $external_ref
 * @property string $name
 * @property string|null $description
 * @property string|null $rarity
 * @property int|null $value
 * @property string|null $weight
 * @property string|null $icon_path
 * @property array<string, mixed>|null $metadata
 * @property string|null $source_note
 * @property-read ItemCategory|null $category
 */
#[Fillable([
    'item_category_id', 'slug', 'external_ref', 'name', 'description', 'rarity', 'value', 'weight', 'icon_path',
    'status', 'metadata', 'source_id', 'source_url', 'source_note', 'confidence_override',
    'last_verified_at', 'introduced_version_id', 'verified_version_id',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasDataQuality, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'published',
        'confidence' => 0,
        'confirmations_count' => 0,
        'open_reports_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => ContentStatus::class,
            'last_verified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<ItemCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Markers where this item is known to be found.
     *
     * @return BelongsToMany<Marker, $this>
     */
    public function markers(): BelongsToMany
    {
        return $this->belongsToMany(Marker::class)->withPivot(['likelihood', 'note'])->withTimestamps();
    }

    /**
     * Loot pools that can drop this item (pivot: chance in percent).
     *
     * @return BelongsToMany<LootTable, $this>
     */
    public function lootTables(): BelongsToMany
    {
        return $this->belongsToMany(LootTable::class)->withPivot('chance')->withTimestamps();
    }

    /**
     * Recipes and upgrades that consume this item.
     *
     * @return BelongsToMany<Recipe, $this>
     */
    public function usedInRecipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')->withPivot('quantity')->withTimestamps();
    }

    /**
     * Recipes that produce this item.
     *
     * @return HasMany<Recipe, $this>
     */
    public function producedBy(): HasMany
    {
        return $this->hasMany(Recipe::class, 'output_item_id');
    }

    /**
     * @return BelongsToMany<Objective, $this>
     */
    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(Objective::class)->withPivot(['role', 'quantity'])->withTimestamps();
    }
}
