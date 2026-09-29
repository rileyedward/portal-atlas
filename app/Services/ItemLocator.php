<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\MapStatus;
use App\Models\Item;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Answers "where can I find this item?" from two relationships:
 *   - markers linked to the item directly (item_marker), and
 *   - markers whose loot table can drop the item (item_loot_table).
 *
 * Results are aggregated per map and per marker name, so an item dropped by
 * hundreds of identical monster spawns shows as one line with a count.
 */
class ItemLocator
{
    /**
     * @return list<array{map: array{slug: string, name: string}, markers: list<array{id: int, name: string, type: string, likelihood: string|null, chance: float|null, count: int}>}>
     */
    public function locate(Item $item, int $perMap = 20): array
    {
        return $this->locateMany([$item->id], $perMap)[$item->id] ?? [];
    }

    /**
     * @param  list<int>  $itemIds
     * @return array<int, list<array{map: array{slug: string, name: string}, markers: list<array{id: int, name: string, type: string, likelihood: string|null, chance: float|null, count: int}>}>>
     */
    public function locateMany(array $itemIds, int $perMap = 20): array
    {
        if ($itemIds === []) {
            return [];
        }

        $viaTables = DB::table('item_loot_table')
            ->join('markers', 'markers.loot_table_id', '=', 'item_loot_table.loot_table_id')
            ->whereIn('item_loot_table.item_id', $itemIds)
            ->select([
                'item_loot_table.item_id',
                'markers.map_id',
                'markers.name',
                'markers.marker_type_id',
                DB::raw('MIN(markers.id) as marker_id'),
                DB::raw('COUNT(*) as marker_count'),
                DB::raw('MAX(item_loot_table.chance) as chance'),
                DB::raw('NULL as likelihood'),
            ]);
        $this->publicMarkers($viaTables)->groupBy('item_loot_table.item_id', 'markers.map_id', 'markers.name', 'markers.marker_type_id');

        $direct = DB::table('item_marker')
            ->join('markers', 'markers.id', '=', 'item_marker.marker_id')
            ->whereIn('item_marker.item_id', $itemIds)
            ->select([
                'item_marker.item_id',
                'markers.map_id',
                'markers.name',
                'markers.marker_type_id',
                DB::raw('MIN(markers.id) as marker_id'),
                DB::raw('COUNT(*) as marker_count'),
                DB::raw('NULL as chance'),
                DB::raw('MAX(item_marker.likelihood) as likelihood'),
            ]);
        $this->publicMarkers($direct)->groupBy('item_marker.item_id', 'markers.map_id', 'markers.name', 'markers.marker_type_id');

        /** @var Collection<int, object{item_id: int, map_id: int, name: string, marker_type_id: int, marker_id: int, marker_count: int, chance: string|float|null, likelihood: string|null}> $rows */
        $rows = $direct->unionAll($viaTables)->get();

        $maps = DB::table('maps')->whereIn('id', $rows->pluck('map_id')->unique())->get(['id', 'slug', 'name', 'sort_order'])->keyBy('id');
        $types = DB::table('marker_types')->whereIn('id', $rows->pluck('marker_type_id')->unique())->pluck('name', 'id');

        $out = [];
        foreach ($rows->groupBy('item_id') as $itemId => $itemRows) {
            $out[(int) $itemId] = array_values($itemRows
                ->groupBy('map_id')
                ->sortBy(fn (Collection $group, int $mapId) => $maps[$mapId]->sort_order ?? 0)
                ->map(fn (Collection $group, int $mapId) => [
                    'map' => ['slug' => (string) $maps[$mapId]->slug, 'name' => (string) $maps[$mapId]->name],
                    'markers' => array_values($group
                        ->sortBy([
                            fn ($a, $b) => ((float) ($b->chance ?? -1)) <=> ((float) ($a->chance ?? -1)),
                            fn ($a, $b) => $b->marker_count <=> $a->marker_count,
                        ])
                        ->take($perMap)
                        ->map(fn ($row) => [
                            'id' => (int) $row->marker_id,
                            'name' => (string) $row->name,
                            'type' => (string) ($types[$row->marker_type_id] ?? ''),
                            'likelihood' => $row->likelihood ?? ($row->chance !== null ? rtrim(rtrim(number_format((float) $row->chance, 2), '0'), '.').'% drop' : null),
                            'chance' => $row->chance !== null ? (float) $row->chance : null,
                            'count' => (int) $row->marker_count,
                        ])
                        ->all()),
                ])
                ->all());
        }

        return $out;
    }

    /**
     * Number of distinct places (map + marker name) each item can be found at.
     *
     * @return array<int, int>
     */
    public function placeCounts(): array
    {
        $viaTables = DB::table('item_loot_table')
            ->join('markers', 'markers.loot_table_id', '=', 'item_loot_table.loot_table_id')
            ->select('item_loot_table.item_id', 'markers.map_id', 'markers.name');
        $this->publicMarkers($viaTables);

        $direct = DB::table('item_marker')
            ->join('markers', 'markers.id', '=', 'item_marker.marker_id')
            ->select('item_marker.item_id', 'markers.map_id', 'markers.name');
        $this->publicMarkers($direct);

        return DB::query()
            ->fromSub($direct->union($viaTables), 'places')
            ->select('item_id', DB::raw('COUNT(*) as total'))
            ->groupBy('item_id')
            ->pluck('total', 'item_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    private function publicMarkers($query)
    {
        return $query
            ->join('maps', 'maps.id', '=', 'markers.map_id')
            ->where('maps.status', MapStatus::Published->value)
            ->where('markers.status', ContentStatus::Published->value)
            ->where('markers.is_visible', true)
            ->whereNull('markers.deleted_at');
    }
}
