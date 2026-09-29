<?php

namespace App\Models;

use App\Enums\MarkerGeometry;
use Database\Factories\MarkerTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $marker_category_id
 * @property string $slug
 * @property string $name
 * @property string|null $icon
 * @property string|null $color
 * @property MarkerGeometry $geometry
 * @property string|null $description
 * @property int $sort_order
 * @property-read MarkerCategory $category
 */
#[Fillable(['marker_category_id', 'slug', 'name', 'icon', 'color', 'geometry', 'description', 'sort_order'])]
class MarkerType extends Model
{
    /** @use HasFactory<MarkerTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['geometry' => MarkerGeometry::class];
    }

    /**
     * @return BelongsTo<MarkerCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarkerCategory::class, 'marker_category_id');
    }

    /**
     * @return HasMany<Marker, $this>
     */
    public function markers(): HasMany
    {
        return $this->hasMany(Marker::class);
    }
}
