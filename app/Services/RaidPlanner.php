<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use Illuminate\Support\Collection;

/**
 * Deterministic raid planning: given items/objectives a player wants, pick
 * the markers that satisfy them on one map, order them with a nearest-
 * neighbour walk, and finish at the closest known extraction.
 *
 * Distances are in normalized map units (percent of map size), so they are a
 * relative guide only — the game's real pathing (walls, elevation) is unknown.
 */
class RaidPlanner
{
    /** Threat markers within this radius of a stop are reported as risks. */
    public const RISK_RADIUS = 8.0;

    /**
     * Items obtainable at each marker: direct links plus its loot table.
     *
     * @var array<int, Collection<int, Item>>
     */
    private array $lootIndex = [];

    /**
     * @return Collection<int, Item>
     */
    private function itemsOf(Marker $marker): Collection
    {
        return $this->lootIndex[$marker->id] ?? $marker->items;
    }

    /**
     * @param  list<int>  $itemIds
     * @param  list<int>  $objectiveIds
     * @param  array{x: float, y: float}|null  $start
     * @return array<string, mixed>
     */
    public function plan(Map $map, array $itemIds, array $objectiveIds, ?array $start = null, ?string $variant = null): array
    {
        $markers = $map->markers()->published()->where('is_visible', true)
            ->whereNotNull('x')->whereNotNull('y')
            ->when($variant !== null, fn ($q) => $q->where(fn ($q) => $q->whereNull('variant')->orWhere('variant', $variant)))
            ->with(['type.category', 'items:id,name,slug', 'lootTable.items:id,name,slug', 'objectives:id,name,slug'])
            ->get();

        $this->lootIndex = $markers->mapWithKeys(fn (Marker $m) => [$m->id => $m->items->concat($m->lootTable->items ?? [])->unique('id')->values()])->all();

        $candidates = $markers->filter(fn (Marker $m) => $this->itemsOf($m)->pluck('id')->intersect($itemIds)->isNotEmpty()
            || $m->objectives->pluck('id')->intersect($objectiveIds)->isNotEmpty());

        $threats = $markers->filter(fn (Marker $m) => $m->type->category->slug === 'threats');
        $extracts = $markers->filter(fn (Marker $m) => $m->type->category->slug === 'extraction');

        $stops = $this->order($candidates->values(), $itemIds, $objectiveIds, $start);
        $last = end($stops) ?: null;
        $from = $last ? ['x' => (float) $last['marker']->x, 'y' => (float) $last['marker']->y] : $start;

        $extract = $from ? $extracts->sortBy(fn (Marker $m) => $this->distance($from, $m))->first() : $extracts->first();

        $coveredItems = collect($stops)->flatMap(fn ($s) => $s['items'])->unique()->values();
        $coveredObjectives = collect($stops)->flatMap(fn ($s) => $s['objectives'])->unique()->values();

        return [
            'stops' => array_map(fn (array $stop) => [
                'marker' => $this->present($stop['marker']),
                'items' => $this->itemsOf($stop['marker'])->whereIn('id', $stop['items'])->pluck('name')->values(),
                'objectives' => $stop['marker']->objectives->whereIn('id', $stop['objectives'])->pluck('name')->values(),
                'risks' => $this->risksNear($stop['marker'], $threats),
            ], $stops),
            'extract' => $extract ? $this->present($extract) : null,
            'extract_options' => $extracts->map(fn (Marker $m) => $this->present($m))->values(),
            'missing_items' => array_values(array_diff($itemIds, $coveredItems->all())),
            'missing_objectives' => array_values(array_diff($objectiveIds, $coveredObjectives->all())),
        ];
    }

    /**
     * Greedy nearest-neighbour ordering that only visits markers that still
     * contribute something not yet covered.
     *
     * @param  Collection<int, Marker>  $candidates
     * @param  list<int>  $itemIds
     * @param  list<int>  $objectiveIds
     * @param  array{x: float, y: float}|null  $start
     * @return list<array{marker: Marker, items: list<int>, objectives: list<int>}>
     */
    private function order(Collection $candidates, array $itemIds, array $objectiveIds, ?array $start): array
    {
        $remainingItems = $itemIds;
        $remainingObjectives = $objectiveIds;
        $position = $start;
        $stops = [];

        while ($candidates->isNotEmpty() && ($remainingItems !== [] || $remainingObjectives !== [])) {
            $scored = $candidates->map(function (Marker $m) use ($remainingItems, $remainingObjectives, $position) {
                $items = array_values(array_intersect($this->itemsOf($m)->pluck('id')->all(), $remainingItems));
                $objectives = array_values(array_intersect($m->objectives->pluck('id')->all(), $remainingObjectives));
                $gain = count($items) + count($objectives);

                return [
                    'marker' => $m,
                    'items' => $items,
                    'objectives' => $objectives,
                    'gain' => $gain,
                    'cost' => $position ? $this->distance($position, $m) : 0.0,
                ];
            })->filter(fn (array $s) => $s['gain'] > 0);

            if ($scored->isEmpty()) {
                break;
            }

            // Prefer the most useful stop, then the closest.
            $best = $scored->sortBy([['gain', 'desc'], ['cost', 'asc']])->first();
            $stops[] = ['marker' => $best['marker'], 'items' => $best['items'], 'objectives' => $best['objectives']];
            $remainingItems = array_values(array_diff($remainingItems, $best['items']));
            $remainingObjectives = array_values(array_diff($remainingObjectives, $best['objectives']));
            $position = ['x' => (float) $best['marker']->x, 'y' => (float) $best['marker']->y];
            $candidates = $candidates->reject(fn (Marker $m) => $m->id === $best['marker']->id);
        }

        return $stops;
    }

    /**
     * @param  Collection<int, Marker>  $threats
     * @return list<string>
     */
    private function risksNear(Marker $marker, Collection $threats): array
    {
        return array_values($threats
            ->filter(fn (Marker $t) => $this->distance(['x' => (float) $marker->x, 'y' => (float) $marker->y], $t) <= self::RISK_RADIUS)
            ->map(fn (Marker $t) => "{$t->type->name}: {$t->name}")
            ->all());
    }

    /**
     * @param  array{x: float, y: float}  $from
     */
    private function distance(array $from, Marker $to): float
    {
        return hypot($from['x'] - (float) $to->x, $from['y'] - (float) $to->y);
    }

    /**
     * @return array{id: int, name: string, x: float|null, y: float|null, type: string}
     */
    private function present(Marker $marker): array
    {
        return ['id' => $marker->id, 'name' => $marker->name, 'x' => $marker->x, 'y' => $marker->y, 'type' => $marker->type->name];
    }
}
