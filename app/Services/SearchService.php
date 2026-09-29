<?php

namespace App\Services;

use App\Enums\MapStatus;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Global search across maps, markers, items and objectives.
 *
 * Uses case-insensitive substring matching (ILIKE on Postgres, LIKE on SQLite). Every word in
 * the query must match. Results are ranked exact > prefix > contains.
 */
class SearchService
{
    public function __construct(private ItemLocator $locator) {}

    public const LIMIT_PER_GROUP = 8;

    /**
     * @return array{query: string, total: int, groups: list<array{key: string, label: string, results: list<array<string, mixed>>}>}
     */
    public function search(string $query, ?Map $scopeMap = null): array
    {
        $query = trim(Str::limit($query, 100, ''));
        $words = $this->words($query);

        if ($words === []) {
            return ['query' => $query, 'total' => 0, 'groups' => []];
        }

        $groups = collect([
            ['key' => 'items', 'label' => 'Items', 'results' => $this->items($words, $query)],
            ['key' => 'markers', 'label' => 'Map markers', 'results' => $this->markers($words, $query, $scopeMap)],
            ['key' => 'objectives', 'label' => 'Objectives', 'results' => $this->objectives($words, $query)],
            ['key' => 'maps', 'label' => 'Maps', 'results' => $this->maps($words, $query)],
        ])->filter(fn (array $group) => $group['results'] !== [])->values();

        return [
            'query' => $query,
            'total' => $groups->sum(fn (array $group) => count($group['results'])),
            'groups' => array_values($groups->all()),
        ];
    }

    /**
     * @return list<string>
     */
    private function words(string $query): array
    {
        return array_values(array_filter(
            preg_split('/\s+/', Str::lower($query)) ?: [],
            fn (string $word) => mb_strlen($word) >= 2,
        ));
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @param  list<string>  $words
     * @param  list<string>  $columns
     * @return Builder<TModel>
     */
    private function matchAll(Builder $builder, array $words, array $columns): Builder
    {
        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $builder->where(function (Builder $q) use ($columns, $like) {
                foreach ($columns as $column) {
                    $q->orWhereLike($column, $like);
                }
            });
        }

        return $builder;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    private function rank(Builder $builder, string $query, string $column = 'name'): Builder
    {
        $escaped = addcslashes(Str::lower($query), '%_\\');

        $sql = $column === 'markers.name'
            ? 'CASE WHEN lower(markers.name) = ? THEN 0 WHEN lower(markers.name) LIKE ? THEN 1 ELSE 2 END'
            : 'CASE WHEN lower(name) = ? THEN 0 WHEN lower(name) LIKE ? THEN 1 ELSE 2 END';

        return $builder
            ->orderByRaw($sql, [Str::lower($query), $escaped.'%'])
            ->orderBy($column);
    }

    /**
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function items(array $words, string $query): array
    {
        $builder = Item::query()->published()->with('category');

        $items = $this->rank($this->matchAll($builder, $words, ['items.name', 'items.description']), $query)
            ->limit(self::LIMIT_PER_GROUP)
            ->get();
        $places = $this->locator->locateMany(array_values($items->map(fn (Item $item) => $item->id)->all()), perMap: 4);

        return array_values($items
            ->map(fn (Item $item) => [
                'type' => 'item',
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'subtitle' => $item->category->name ?? 'Item',
                'rarity' => $item->rarity,
                'found_at' => $places[$item->id] ?? [],
            ])->all());
    }

    /**
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function markers(array $words, string $query, ?Map $scopeMap): array
    {
        $builder = Marker::query()
            ->published()
            ->where('is_visible', true)
            ->whereHas('map', fn (Builder $q) => $q->where('status', MapStatus::Published->value))
            ->with(['map:id,slug,name', 'type.category'])
            ->when($scopeMap, fn (Builder $q, Map $map) => $q->orderByRaw('CASE WHEN markers.map_id = ? THEN 0 ELSE 1 END', [$map->id]));

        return array_values($this->rank($this->matchAll($builder, $words, ['markers.name', 'markers.description']), $query, 'markers.name')
            ->limit(self::LIMIT_PER_GROUP)
            ->get()
            ->map(fn (Marker $marker) => [
                'type' => 'marker',
                'id' => $marker->id,
                'name' => $marker->name,
                'subtitle' => $marker->type->name.' · '.$marker->map->name,
                'category' => $marker->type->category->slug,
                'map' => ['slug' => $marker->map->slug, 'name' => $marker->map->name],
                'x' => $marker->x,
                'y' => $marker->y,
            ])->all());
    }

    /**
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function objectives(array $words, string $query): array
    {
        $builder = Objective::query()->published()->with('map:id,slug,name');

        return array_values($this->rank($this->matchAll($builder, $words, ['objectives.name', 'objectives.description']), $query)
            ->limit(self::LIMIT_PER_GROUP)
            ->get()
            ->map(fn (Objective $objective) => [
                'type' => 'objective',
                'id' => $objective->id,
                'slug' => $objective->slug,
                'name' => $objective->name,
                'subtitle' => $objective->kind->label().($objective->map ? ' · '.$objective->map->name : ''),
            ])->all());
    }

    /**
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function maps(array $words, string $query): array
    {
        $builder = Map::query()->published();

        return array_values($this->rank($this->matchAll($builder, $words, ['maps.name', 'maps.summary']), $query)
            ->limit(self::LIMIT_PER_GROUP)
            ->get()
            ->map(fn (Map $map) => [
                'type' => 'map',
                'id' => $map->id,
                'slug' => $map->slug,
                'name' => $map->name,
                'subtitle' => 'Map',
            ])->all());
    }
}
