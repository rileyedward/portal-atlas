<?php

namespace App\Http\Controllers;

use App\Enums\MapStatus;
use App\Models\Item;
use App\Models\Map;
use App\Models\Objective;
use App\Services\ItemLocator;
use App\Services\RaidPlanner;
use App\Support\PublicCache;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RaidPlannerController extends Controller
{
    public function show(Request $request, ItemLocator $locator, PublicCache $cache): Response
    {
        $needed = $request->user()?->trackedItems()
            ->wherePivot('intent', 'need')
            ->pluck('items.id')
            ->all() ?? [];

        return Inertia::render('planner/Show', [
            'seo' => Seo::make(
                'Active Matter Raid Planner',
                'Plan an Active Matter raid: pick the items and objectives you need and get an ordered list of documented locations, nearby threats and the closest known extraction.',
            ),
            ...$cache->remember('planner', fn () => [
                'maps' => Map::query()->published()->orderBy('sort_order')->get(['id', 'slug', 'name', 'metadata'])
                    ->map(fn (Map $map) => [
                        'id' => $map->id,
                        'slug' => $map->slug,
                        'name' => $map->name,
                        'variants' => $map->metadata['variant_options'] ?? [],
                    ])->all(),
                // Only items with at least one known location can be planned for.
                'items' => Item::query()->published()
                    ->whereIn('id', array_keys($locator->placeCounts()))
                    ->orderBy('name')->get(['id', 'slug', 'name'])->toArray(),
                'objectives' => Objective::query()->published()->whereHas('markers')->orderBy('name')->get(['id', 'slug', 'name', 'map_id'])->toArray(),
            ]),
            'neededItemIds' => $needed,
        ]);
    }

    public function plan(Request $request, RaidPlanner $planner): JsonResponse
    {
        $validated = $request->validate([
            'map' => ['required', 'string', Rule::exists('maps', 'slug')->where('status', MapStatus::Published->value)],
            'items' => ['array', 'max:30'],
            'items.*' => ['integer'],
            'objectives' => ['array', 'max:20'],
            'objectives.*' => ['integer'],
            'variant' => ['nullable', 'string', 'max:40'],
            'start' => ['nullable', 'array'],
            'start.x' => ['required_with:start', 'numeric', 'between:0,100'],
            'start.y' => ['required_with:start', 'numeric', 'between:0,100'],
        ]);

        $map = Map::where('slug', $validated['map'])->firstOrFail();
        Gate::authorize('view', $map);

        /** @var array{x: float, y: float}|null $start */
        $start = isset($validated['start']) ? ['x' => (float) $validated['start']['x'], 'y' => (float) $validated['start']['y']] : null;

        return response()->json($planner->plan(
            $map,
            array_values(array_map('intval', $validated['items'] ?? [])),
            array_values(array_map('intval', $validated['objectives'] ?? [])),
            $start,
            $validated['variant'] ?? null,
        ));
    }
}
