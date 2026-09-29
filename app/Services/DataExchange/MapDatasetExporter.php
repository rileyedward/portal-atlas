<?php

namespace App\Services\DataExchange;

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;

/**
 * Exports one map's markers in the portable `active-matter-map/v1` format.
 */
class MapDatasetExporter
{
    public const FORMAT = 'active-matter-map/v1';

    /**
     * @return array<string, mixed>
     */
    public function export(Map $map): array
    {
        $map->load('gameVersion');

        $markers = $map->markers()
            ->with(['type', 'source', 'lootTable:id,key', 'items:id,slug', 'objectives:id,slug', 'verifiedVersion', 'introducedVersion'])
            ->orderBy('id')
            ->get();

        return [
            'format' => self::FORMAT,
            'exported_at' => now()->toIso8601String(),
            'map' => $map->slug,
            'map_name' => $map->name,
            'version' => $map->gameVersion?->version,
            'markers' => $markers->map(fn (Marker $m) => array_filter([
                'id' => $m->id,
                'external_ref' => $m->external_ref,
                'type' => $m->type->slug,
                'name' => $m->name,
                'description' => $m->description,
                'x' => $m->x,
                'y' => $m->y,
                'geometry' => $m->geometry,
                'floor' => $m->floor,
                'variant' => $m->variant,
                'loot_table' => $m->lootTable?->key,
                'status' => $m->status->value,
                'source' => $m->source?->name,
                'source_url' => $m->source_url,
                'source_note' => $m->source_note,
                'confidence_override' => $m->confidence_override,
                'introduced_version' => $m->introducedVersion?->version,
                'verified_version' => $m->verifiedVersion?->version,
                'last_verified_at' => $m->last_verified_at?->toIso8601String(),
                'items' => $m->items->map(fn (Item $i) => $i->slug)->values()->all() ?: null,
                'objectives' => $m->objectives->map(fn (Objective $o) => $o->slug)->values()->all() ?: null,
                'metadata' => $m->metadata ?: null,
            ], fn ($value) => $value !== null))->values()->all(),
        ];
    }
}
