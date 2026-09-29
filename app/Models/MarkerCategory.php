<?php

namespace App\Models;

use Database\Factories\MarkerCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $color
 * @property string $icon
 * @property bool $visible_by_default
 * @property int $sort_order
 */
#[Fillable(['slug', 'name', 'color', 'icon', 'visible_by_default', 'sort_order'])]
class MarkerCategory extends Model
{
    /** @use HasFactory<MarkerCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['visible_by_default' => 'boolean'];
    }

    /**
     * @return HasMany<MarkerType, $this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(MarkerType::class)->orderBy('sort_order');
    }
}
