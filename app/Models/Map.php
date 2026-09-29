<?php

namespace App\Models;

use App\Enums\MapStatus;
use Database\Factories\MapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $summary
 * @property string|null $description
 * @property MapStatus $status
 * @property string|null $image_path
 * @property string|null $image_attribution
 * @property int $width
 * @property int $height
 * @property int $sort_order
 * @property int|null $game_version_id
 * @property int|null $source_id
 * @property string|null $source_url
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'slug', 'name', 'summary', 'description', 'status', 'image_path', 'image_attribution',
    'width', 'height', 'sort_order', 'game_version_id', 'source_id', 'source_url', 'metadata',
])]
class Map extends Model
{
    /** @use HasFactory<MapFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'width' => 1000,
        'height' => 1000,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => MapStatus::class,
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Marker, $this>
     */
    public function markers(): HasMany
    {
        return $this->hasMany(Marker::class);
    }

    /**
     * Feedback about the map itself.
     *
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * @return HasMany<Objective, $this>
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }

    /**
     * @return BelongsTo<GameVersion, $this>
     */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
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
        $query->where('status', MapStatus::Published->value);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
