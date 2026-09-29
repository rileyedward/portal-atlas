<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MapResource;
use App\Http\Resources\MarkerCategoryResource;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\LootTable;
use App\Models\Map;
use App\Models\MarkerCategory;
use App\Models\Objective;
use App\Models\Source;
use Inertia\Inertia;
use Inertia\Response;

class MapEditorController extends Controller
{
    public function __invoke(Map $map): Response
    {
        $markers = $map->markers()
            ->get(['id', 'marker_type_id', 'name', 'x', 'y', 'geometry', 'floor', 'variant', 'status', 'is_visible', 'confidence', 'confidence_override', 'open_reports_count']);

        return Inertia::render('admin/maps/Editor', [
            'map' => new MapResource($map->load('gameVersion'))->resolve(),
            'categories' => MarkerCategoryResource::collection(MarkerCategory::with('types')->orderBy('sort_order')->get())->resolve(),
            'markers' => $markers->map(fn ($m) => [
                ...$m->only(['id', 'marker_type_id', 'name', 'x', 'y', 'geometry', 'floor', 'variant', 'is_visible', 'open_reports_count']),
                'status' => $m->status->value,
                'confidence' => $m->effectiveConfidence(),
            ]),
            'options' => [
                'items' => Item::orderBy('name')->get(['id', 'name']),
                'lootTables' => LootTable::orderBy('name')->get(['id', 'name']),
                'objectives' => Objective::orderBy('name')->get(['id', 'name']),
                'sources' => Source::orderBy('name')->get(['id', 'name']),
                'versions' => GameVersion::orderByDesc('released_at')->get(['id', 'version']),
                'statuses' => ContentStatus::options(),
            ],
        ]);
    }
}
