<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasDataQuality;
use Database\Factories\MarkerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $map_id
 * @property int $marker_type_id
 * @property string $name
 * @property string|null $description
 * @property float|null $x
 * @property float|null $y
 * @property list<array{0: float, 1: float}>|null $geometry
 * @property string|null $floor
 * @property string|null $variant
 * @property int|null $loot_table_id
 * @property string|null $external_ref
 * @property bool $is_visible
 * @property array<string, mixed>|null $metadata
 * @property string|null $source_note
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read Map $map
 * @property-read MarkerType $type
 * @property-read LootTable|null $lootTable
 */
#[Fillable([
    'map_id', 'marker_type_id', 'name', 'description', 'x', 'y', 'geometry', 'floor', 'variant', 'loot_table_id', 'external_ref', 'status',
    'is_visible', 'metadata', 'source_id', 'source_url', 'source_note', 'confidence_override',
    'last_verified_at', 'introduced_version_id', 'verified_version_id', 'created_by', 'updated_by',
])]
class Marker extends Model
{
    /** @use HasFactory<MarkerFactory> */
    use HasDataQuality, HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'published',
        'is_visible' => true,
        'confidence' => 0,
        'confirmations_count' => 0,
        'open_reports_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'geometry' => 'array',
            'metadata' => 'array',
            'is_visible' => 'boolean',
            'status' => ContentStatus::class,
            'last_verified_at' => 'datetime',
        ];
    }

    public function isPlaced(): bool
    {
        return $this->x !== null && $this->y !== null;
    }

    /**
     * @return BelongsTo<Map, $this>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * @return BelongsTo<MarkerType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MarkerType::class, 'marker_type_id');
    }

    /**
     * @return BelongsToMany<Item, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->withPivot(['likelihood', 'note'])->withTimestamps();
    }

    /**
     * @return BelongsTo<LootTable, $this>
     */
    public function lootTable(): BelongsTo
    {
        return $this->belongsTo(LootTable::class);
    }

    /**
     * @return BelongsToMany<Objective, $this>
     */
    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(Objective::class)->withPivot('role')->withTimestamps();
    }
}
