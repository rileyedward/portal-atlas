<?php

namespace App\Services\DataExchange;

use App\Enums\ContentStatus;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\LootTable;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use App\Models\Objective;
use App\Models\Source;
use App\Models\User;
use App\Services\ConfidenceCalculator;
use App\Support\PublicCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Imports markers in the `active-matter-map/v1` format.
 *
 * Matching: a marker with an `id` that belongs to the target map is updated;
 * otherwise a marker with the same type + name on the map is updated;
 * otherwise a new marker is created. Nothing is ever deleted by an import.
 */
class MapDatasetImporter
{
    public function __construct(private ConfidenceCalculator $confidence, private PublicCache $cache) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, bool $dryRun = true, ?User $actor = null, ?Map $expectedMap = null): ImportResult
    {
        $result = new ImportResult($dryRun);

        $validator = Validator::make($payload, [
            'format' => ['nullable', Rule::in([MapDatasetExporter::FORMAT])],
            'map' => ['required', 'string'],
            'version' => ['nullable', 'string', 'max:50'],
            'markers' => ['present', 'array', 'max:10000'],
            'markers.*.id' => ['nullable', 'integer'],
            'markers.*.external_ref' => ['nullable', 'string', 'max:120'],
            'markers.*.variant' => ['nullable', 'string', 'max:40'],
            'markers.*.loot_table' => ['nullable', 'string', 'max:255'],
            'markers.*.type' => ['required', 'string'],
            'markers.*.name' => ['required', 'string', 'max:255'],
            'markers.*.description' => ['nullable', 'string', 'max:5000'],
            'markers.*.x' => ['nullable', 'required_with:markers.*.y', 'numeric', 'between:0,100'],
            'markers.*.y' => ['nullable', 'required_with:markers.*.x', 'numeric', 'between:0,100'],
            'markers.*.geometry' => ['nullable', 'array', 'max:500'],
            'markers.*.geometry.*' => ['array', 'size:2'],
            'markers.*.geometry.*.*' => ['numeric', 'between:0,100'],
            'markers.*.floor' => ['nullable', 'string', 'max:50'],
            'markers.*.status' => ['nullable', Rule::enum(ContentStatus::class)],
            'markers.*.source' => ['nullable', 'string', 'max:255'],
            'markers.*.source_url' => ['nullable', 'url', 'max:255'],
            'markers.*.source_note' => ['nullable', 'string', 'max:2000'],
            'markers.*.confidence_override' => ['nullable', 'integer', 'between:0,100'],
            'markers.*.introduced_version' => ['nullable', 'string', 'max:50'],
            'markers.*.verified_version' => ['nullable', 'string', 'max:50'],
            'markers.*.last_verified_at' => ['nullable', 'date'],
            'markers.*.items' => ['nullable', 'array'],
            'markers.*.items.*' => ['string'],
            'markers.*.objectives' => ['nullable', 'array'],
            'markers.*.objectives.*' => ['string'],
            'markers.*.metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            $result->errors = array_values($validator->errors()->all());

            return $result;
        }

        /** @var array<string, mixed> $data */
        $data = $validator->validated();

        $map = Map::where('slug', $data['map'])->first();
        if (! $map) {
            $result->errors[] = "Map \"{$data['map']}\" does not exist. Create it first.";

            return $result;
        }
        if ($expectedMap && $expectedMap->id !== $map->id) {
            $result->errors[] = "This file is for map \"{$map->slug}\", not \"{$expectedMap->slug}\".";

            return $result;
        }

        $types = MarkerType::pluck('id', 'slug');
        $items = Item::pluck('id', 'slug');
        $objectives = Objective::pluck('id', 'slug');
        $versions = GameVersion::pluck('id', 'version');
        $sources = Source::pluck('id', 'name');
        $reliability = Source::pluck('reliability', 'id');
        $lootTables = LootTable::pluck('id', 'key');

        // Preload the whole map once: a remote database makes per-row queries
        // (thousands of round-trips) the dominant cost of an import.
        $existing = Marker::withTrashed()->where('map_id', $map->id)->get();
        $byExternalRef = $existing->whereNotNull('external_ref')->keyBy('external_ref');
        $byId = $existing->keyBy('id');
        $byTypeAndName = [];
        foreach ($existing as $marker) {
            if (! $marker->trashed()) {
                $byTypeAndName[$marker->marker_type_id.'|'.$marker->name] ??= $marker;
            }
        }

        // Markers with community reports or confirmations need a full confidence refresh.
        $morph = (new Marker)->getMorphClass();
        $flagged = array_flip(array_merge(
            DB::table('reports')->join('markers', 'markers.id', '=', 'reports.reportable_id')
                ->where('reports.reportable_type', $morph)->where('markers.map_id', $map->id)
                ->pluck('reports.reportable_id')->all(),
            DB::table('verifications')->join('markers', 'markers.id', '=', 'verifications.verifiable_id')
                ->where('verifications.verifiable_type', $morph)->where('markers.map_id', $map->id)
                ->pluck('verifications.verifiable_id')->all(),
        ));

        try {
            DB::transaction(function () use ($data, $map, $types, $items, $objectives, $versions, $sources, $reliability, $lootTables, $byExternalRef, $byId, $byTypeAndName, $flagged, $result, $actor, $dryRun) {
                /** @var list<array<string, mixed>> $rows */
                $rows = $data['markers'];
                $now = now();
                $inserts = [];
                /** @var array<string, array{items: list<int>, objectives: list<int>}> $pendingLinks  keyed by match key of new markers */
                $pendingLinks = [];

                foreach ($rows as $index => $row) {
                    $label = "Marker #{$index} ({$row['name']})";

                    $typeId = $types[$row['type']] ?? null;
                    if ($typeId === null) {
                        $result->errors[] = "{$label}: unknown type \"{$row['type']}\".";

                        continue;
                    }

                    $itemIds = [];
                    foreach ($row['items'] ?? [] as $slug) {
                        isset($items[$slug]) ? $itemIds[] = $items[$slug] : $result->errors[] = "{$label}: unknown item \"{$slug}\".";
                    }
                    $objectiveIds = [];
                    foreach ($row['objectives'] ?? [] as $slug) {
                        isset($objectives[$slug]) ? $objectiveIds[] = $objectives[$slug] : $result->errors[] = "{$label}: unknown objective \"{$slug}\".";
                    }
                    if (isset($row['source']) && ! isset($sources[$row['source']])) {
                        $result->warnings[] = "{$label}: unknown source \"{$row['source']}\" — ignored.";
                    }
                    foreach (['introduced_version', 'verified_version'] as $key) {
                        if (isset($row[$key]) && ! isset($versions[$row[$key]])) {
                            $result->warnings[] = "{$label}: unknown game version \"{$row[$key]}\" — ignored.";
                        }
                    }
                    if (isset($row['loot_table']) && ! isset($lootTables[$row['loot_table']])) {
                        $result->warnings[] = "{$label}: unknown loot table \"{$row['loot_table']}\" — ignored.";
                    }

                    $name = strip_tags($row['name']);

                    // Match: external_ref (authoritative when present) → id → type + name.
                    if (isset($row['external_ref'])) {
                        $marker = $byExternalRef->get($row['external_ref']);
                        $matchKey = 'ref:'.$row['external_ref'];
                    } else {
                        $marker = isset($row['id']) ? $byId->get((int) $row['id']) : null;
                        $marker ??= $byTypeAndName[$typeId.'|'.$name] ?? null;
                        $matchKey = 'tn:'.$typeId.'|'.$name;
                    }

                    $sourceId = isset($row['source']) ? ($sources[$row['source']] ?? null) : null;
                    $verifiedVersionId = isset($row['verified_version']) ? ($versions[$row['verified_version']] ?? null) : null;
                    $lastVerifiedAt = isset($row['last_verified_at']) ? Carbon::parse($row['last_verified_at']) : null;

                    $attributes = [
                        'map_id' => $map->id,
                        'marker_type_id' => $typeId,
                        'name' => $name,
                        'description' => $row['description'] ?? null,
                        'x' => isset($row['x']) ? round((float) $row['x'], 4) : null,
                        'y' => isset($row['y']) ? round((float) $row['y'], 4) : null,
                        'geometry' => $row['geometry'] ?? null,
                        'floor' => $row['floor'] ?? null,
                        'variant' => $row['variant'] ?? null,
                        'loot_table_id' => isset($row['loot_table']) ? ($lootTables[$row['loot_table']] ?? null) : null,
                        'external_ref' => $row['external_ref'] ?? $marker?->external_ref,
                        'status' => $row['status'] ?? ContentStatus::Published->value,
                        'source_id' => $sourceId,
                        'source_url' => $row['source_url'] ?? null,
                        'source_note' => $row['source_note'] ?? null,
                        'confidence_override' => $row['confidence_override'] ?? null,
                        'introduced_version_id' => isset($row['introduced_version']) ? ($versions[$row['introduced_version']] ?? null) : null,
                        'verified_version_id' => $verifiedVersionId,
                        'last_verified_at' => $lastVerifiedAt,
                        'metadata' => $row['metadata'] ?? null,
                        'updated_by' => $actor?->id,
                        'confidence' => $this->confidence->baseline(
                            $sourceId !== null ? (int) ($reliability[$sourceId] ?? 0) : null,
                            $verifiedVersionId,
                            $lastVerifiedAt,
                        ),
                    ];

                    if ($marker) {
                        $marker->forceFill($attributes);
                        $changed = $marker->isDirty() || $marker->trashed();
                        if ($changed) {
                            $marker->deleted_at = null;
                            $marker->save();
                        }
                        $changed ? $result->updated++ : $result->unchanged++;

                        if ($itemIds !== []) {
                            $marker->items()->syncWithoutDetaching($itemIds);
                        }
                        if ($objectiveIds !== []) {
                            $marker->objectives()->syncWithoutDetaching($objectiveIds);
                        }
                        if (isset($flagged[$marker->id])) {
                            $this->confidence->refresh($marker);
                        }

                        continue;
                    }

                    $inserts[] = [
                        ...$attributes,
                        'geometry' => $attributes['geometry'] !== null ? json_encode($attributes['geometry']) : null,
                        'metadata' => $attributes['metadata'] !== null ? json_encode($attributes['metadata']) : null,
                        'last_verified_at' => $lastVerifiedAt?->toDateTimeString(),
                        'is_visible' => true,
                        'confirmations_count' => 0,
                        'open_reports_count' => 0,
                        'created_by' => $actor?->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($itemIds !== [] || $objectiveIds !== []) {
                        $pendingLinks[$matchKey] = ['items' => $itemIds, 'objectives' => $objectiveIds];
                    }
                    $result->created++;
                }

                foreach (array_chunk($inserts, 500) as $chunk) {
                    DB::table('markers')->insert($chunk);
                }

                // Link items/objectives for newly created markers (rare; one lookup query).
                if ($pendingLinks !== []) {
                    $created = Marker::where('map_id', $map->id)->get(['id', 'external_ref', 'marker_type_id', 'name']);
                    foreach ($created as $marker) {
                        $key = $marker->external_ref !== null ? 'ref:'.$marker->external_ref : 'tn:'.$marker->marker_type_id.'|'.$marker->name;
                        if (isset($pendingLinks[$key])) {
                            $marker->items()->syncWithoutDetaching($pendingLinks[$key]['items']);
                            $marker->objectives()->syncWithoutDetaching($pendingLinks[$key]['objectives']);
                            unset($pendingLinks[$key]);
                        }
                    }
                }

                if ($dryRun || $result->errors !== []) {
                    throw new RollbackImport;
                }
            });

            $this->cache->flush();
        } catch (RollbackImport) {
            // Dry run or validation errors: nothing is persisted.
        } catch (Throwable $e) {
            report($e);
            $result->errors[] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }
}
