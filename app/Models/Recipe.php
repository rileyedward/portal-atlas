<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\RecipeKind;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A crafting recipe or an upgrade: something that consumes items.
 *
 * @property int $id
 * @property RecipeKind $kind
 * @property string $slug
 * @property string $name
 * @property string|null $station
 * @property int|null $level
 * @property int|null $output_item_id
 * @property int $output_quantity
 * @property string|null $description
 * @property ContentStatus $status
 * @property int|null $source_id
 * @property string|null $source_url
 * @property int $confidence
 * @property int|null $verified_version_id
 * @property-read Item|null $outputItem
 */
#[Fillable([
    'kind', 'slug', 'name', 'station', 'level', 'output_item_id', 'output_quantity', 'description',
    'status', 'source_id', 'source_url', 'confidence', 'verified_version_id',
])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'published',
        'confidence' => 0,
        'output_quantity' => 1,
    ];

    protected function casts(): array
    {
        return [
            'kind' => RecipeKind::class,
            'status' => ContentStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Item, $this>
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'recipe_ingredients')->withPivot('quantity')->withTimestamps();
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published->value);
    }
}
