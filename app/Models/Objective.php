<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ObjectiveKind;
use App\Models\Concerns\HasDataQuality;
use Database\Factories\ObjectiveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int|null $map_id
 * @property ObjectiveKind $kind
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $giver
 * @property string|null $rewards
 * @property array<string, mixed>|null $metadata
 * @property string|null $source_note
 * @property-read Map|null $map
 */
#[Fillable([
    'map_id', 'kind', 'slug', 'name', 'description', 'giver', 'rewards', 'status', 'metadata',
    'source_id', 'source_url', 'source_note', 'confidence_override', 'last_verified_at',
    'introduced_version_id', 'verified_version_id',
])]
class Objective extends Model
{
    /** @use HasFactory<ObjectiveFactory> */
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
            'kind' => ObjectiveKind::class,
            'status' => ContentStatus::class,
            'metadata' => 'array',
            'last_verified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Map, $this>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * @return BelongsToMany<Marker, $this>
     */
    public function markers(): BelongsToMany
    {
        return $this->belongsToMany(Marker::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Item, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->withPivot(['role', 'quantity'])->withTimestamps();
    }
}
