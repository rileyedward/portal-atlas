<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Support\ConfidenceBreakdown;
use App\Support\Pivot;
use App\Support\SourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full marker payload for the detail panel. Unknown values stay null so the
 * UI can render them explicitly as "Unknown".
 *
 * @mixin Marker
 */
class MarkerDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $confidence = $this->effectiveConfidence();
        $showSources = SourceVisibility::visibleTo($request->user());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'x' => $this->x,
            'y' => $this->y,
            'floor' => $this->floor,
            'variant' => $this->variant,
            'status' => $this->status->value,
            'map' => ['slug' => $this->map->slug, 'name' => $this->map->name],
            'type' => [
                'id' => $this->type->id,
                'name' => $this->type->name,
                'category' => $this->type->category->name,
                'category_slug' => $this->type->category->slug,
            ],
            'items' => $this->items->map(fn (Item $item) => [
                'slug' => $item->slug,
                'name' => $item->name,
                'rarity' => $item->rarity,
                'likelihood' => Pivot::get($item, 'likelihood'),
                'note' => Pivot::get($item, 'note'),
            ])->values(),
            'loot_table' => $this->lootTable ? [
                'name' => $this->lootTable->name,
                'items' => $this->lootTable->items
                    ->sortByDesc(fn (Item $item) => (float) Pivot::get($item, 'chance'))
                    ->map(fn (Item $item) => [
                        'slug' => $item->slug,
                        'name' => $item->name,
                        'rarity' => $item->rarity,
                        'chance' => Pivot::get($item, 'chance') !== null ? (float) Pivot::get($item, 'chance') : null,
                    ])->values(),
            ] : null,
            'objectives' => $this->objectives->map(fn (Objective $objective) => [
                'slug' => $objective->slug,
                'name' => $objective->name,
                'kind' => $objective->kind->label(),
                'role' => Pivot::get($objective, 'role'),
            ])->values(),
            'confidence' => [
                'score' => $confidence,
                'label' => ConfidenceBreakdown::labelFor($confidence),
                'confirmations' => $this->confirmations_count,
                'open_reports' => $this->open_reports_count,
            ],
            // Provenance is for editors only; players see confidence and dates.
            'source' => $this->when($showSources, fn () => $this->source ? [
                'name' => $this->source->name,
                'kind' => $this->source->kind->label(),
                'url' => $this->source_url ?? $this->source->url,
            ] : ($this->source_url ? ['name' => null, 'kind' => null, 'url' => $this->source_url] : null)),
            'source_note' => $this->when($showSources, $this->source_note),
            'last_verified_at' => $this->last_verified_at?->toIso8601String(),
            'introduced_version' => $this->introducedVersion?->version,
            'verified_version' => $this->verifiedVersion?->version,
            'metadata' => (object) SourceVisibility::filterMetadata($this->metadata, SourceVisibility::PUBLIC_MARKER_METADATA, $request->user()),
        ];
    }
}
