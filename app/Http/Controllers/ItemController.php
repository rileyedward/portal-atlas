<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LootTable;
use App\Models\Map;
use App\Models\Objective;
use App\Models\Recipe;
use App\Services\Analytics;
use App\Services\ItemLocator;
use App\Services\KeepAdvisor;
use App\Support\ConfidenceBreakdown;
use App\Support\Pivot;
use App\Support\PublicCache;
use App\Support\Seo;
use App\Support\SourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(ItemLocator $locator, PublicCache $cache): Response
    {
        return Inertia::render('items/Index', [
            'seo' => Seo::make(
                'Active Matter Item Database',
                'Searchable Active Matter item database: categories, rarity, known loot locations and confidence scores for every item, with sources.',
            ),
            ...$cache->remember('items.index', function () use ($locator) {
                $places = $locator->placeCounts();

                return [
                    'items' => Item::query()->published()
                        ->with('category')
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Item $item) => [
                            'slug' => $item->slug,
                            'name' => $item->name,
                            'category' => $item->category?->name,
                            'rarity' => $item->rarity,
                            'locations' => $places[$item->id] ?? 0,
                            'confidence' => $item->effectiveConfidence(),
                        ])->all(),
                    'categories' => ItemCategory::orderBy('sort_order')->orderBy('name')->pluck('name')->all(),
                ];
            }),
        ]);
    }

    public function show(Request $request, Item $item, KeepAdvisor $advisor, Analytics $analytics, ItemLocator $locator, PublicCache $cache): Response
    {
        abort_unless($item->status->value === 'published' || $request->user()?->canManageContent(), 404);

        $analytics->record('item_view', 'item', $item->id);

        // KeepAdvisor reads these; load the published subset so it never falls back to drafts.
        $item->load([
            'category', 'source', 'verifiedVersion', 'introducedVersion',
            'usedInRecipes' => fn ($q) => $q->published(),
            'objectives' => fn ($q) => $q->published(),
        ]);

        $confidence = $item->effectiveConfidence();
        $tracking = $request->user()?->trackedItems()->whereKey($item->id)->first()?->pivot;
        $related = $cache->remember("item.{$item->id}.related", fn () => $this->related($item, $locator));

        return Inertia::render('items/Show', [
            'seo' => Seo::make("{$item->name} – Where to Find It in Active Matter", $this->metaDescription($item, $related)),
            'item' => [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'description' => $item->description,
                'category' => $item->category?->name,
                'rarity' => $item->rarity,
                'value' => $item->value,
                'weight' => $item->weight,
                'metadata' => (object) SourceVisibility::filterMetadata($item->metadata, SourceVisibility::PUBLIC_ITEM_METADATA, $request->user()),
                'confidence' => ['score' => $confidence, 'label' => ConfidenceBreakdown::labelFor($confidence)],
                ...(SourceVisibility::visibleTo($request->user()) ? [
                    'source' => $item->source ? ['name' => $item->source->name, 'url' => $item->source_url ?? $item->source->url] : null,
                    'source_url' => $item->source_url,
                ] : []),
                'last_verified_at' => $item->last_verified_at?->toIso8601String(),
                'verified_version' => $item->verifiedVersion?->version,
            ],
            ...$related,
            'advice' => $advisor->advise($item, $request->user()),
            'tracking' => $tracking ? [
                'intent' => $tracking->getAttribute('intent'),
                'quantity_needed' => (int) $tracking->getAttribute('quantity_needed'),
                'quantity_owned' => (int) $tracking->getAttribute('quantity_owned'),
                'is_favorite' => (bool) $tracking->getAttribute('is_favorite'),
            ] : null,
            'reportTypes' => ReportType::options(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $related
     */
    private function metaDescription(Item $item, array $related): string
    {
        $maps = count($related['foundAt']);
        $recipes = count($related['usedIn']);
        $locations = 0;
        foreach ($related['foundAt'] as $group) {
            foreach (data_get($group, 'markers', []) as $marker) {
                $locations += (int) data_get($marker, 'count', 1);
            }
        }

        return collect([
            $item->description ? Str::limit($item->description, 120) : "{$item->name} in Active Matter.",
            $locations
                ? "{$locations} known ".Str::plural('location', $locations)." on {$maps} ".Str::plural('map', $maps).'.'
                : 'No mapped locations yet.',
            $recipes ? "Used in {$recipes} ".Str::plural('recipe', $recipes).' or '.Str::plural('upgrade', $recipes).'.' : null,
        ])->filter()->implode(' ');
    }

    /**
     * Where the item is found and what it is used for. Shared by every viewer.
     *
     * @return array<string, mixed>
     */
    private function related(Item $item, ItemLocator $locator): array
    {
        $item->load([
            'markers' => fn ($q) => $q->published()->where('is_visible', true)->with(['map', 'type']),
            'usedInRecipes' => fn ($q) => $q->published()->with('ingredients'),
            'producedBy' => fn ($q) => $q->published()->with('ingredients'),
            'objectives' => fn ($q) => $q->published()->with('map'),
        ]);

        return [
            'foundAt' => $locator->locate($item),
            'lootPools' => $this->lootPools($item),
            'usedIn' => $item->usedInRecipes->map(fn (Recipe $r) => $this->recipe($r, Pivot::int($r, 'quantity')))->values()->all(),
            'producedBy' => $item->producedBy->map(fn (Recipe $r) => $this->recipe($r))->values()->all(),
            'objectives' => $item->objectives->map(fn (Objective $o) => [
                'slug' => $o->slug,
                'name' => $o->name,
                'kind' => $o->kind->label(),
                'map' => $o->map?->name,
                'role' => Pivot::get($o, 'role'),
                'quantity' => Pivot::get($o, 'quantity'),
            ])->values()->all(),
        ];
    }

    /**
     * Loot pools that can drop the item, highest chance first.
     *
     * @return list<array{name: string, chance: float|null, map: array{slug: string, name: string}|null, spots: int}>
     */
    private function lootPools(Item $item): array
    {
        $pools = $item->lootTables()->withCount(['markers' => fn ($q) => $q->published()])->get();
        $maps = Map::query()->published()->pluck('name', 'slug');

        return array_values($pools
            ->sortByDesc(fn (LootTable $pool) => (float) Pivot::get($pool, 'chance'))
            ->take(60)
            ->map(function (LootTable $pool) use ($maps) {
                $slug = is_string($pool->metadata['map'] ?? null) ? $pool->metadata['map'] : null;
                $chance = Pivot::get($pool, 'chance');

                return [
                    'name' => $pool->name,
                    'chance' => $chance !== null ? (float) $chance : null,
                    'map' => $slug !== null && isset($maps[$slug]) ? ['slug' => $slug, 'name' => (string) $maps[$slug]] : null,
                    'spots' => (int) $pool->getAttribute('markers_count'),
                ];
            })
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function recipe(Recipe $recipe, ?int $quantity = null): array
    {
        return [
            'slug' => $recipe->slug,
            'name' => $recipe->name,
            'kind' => $recipe->kind->label(),
            'station' => $recipe->station,
            'level' => $recipe->level,
            'quantity' => $quantity,
            'ingredients' => $recipe->ingredients->map(fn (Item $i) => [
                'slug' => $i->slug,
                'name' => $i->name,
                'quantity' => Pivot::int($i, 'quantity'),
            ])->values()->all(),
        ];
    }
}
