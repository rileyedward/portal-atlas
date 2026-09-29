<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Http\Resources\MapResource;
use App\Http\Resources\MapSummaryResource;
use App\Http\Resources\MarkerCategoryResource;
use App\Http\Resources\MarkerResource;
use App\Models\Map;
use App\Models\MarkerCategory;
use App\Models\RaidRoute;
use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function show(Request $request, Map $map, Analytics $analytics): Response
    {
        Gate::authorize('view', $map);

        $analytics->record('map_view', 'map', $map->id);

        $markers = $map->markers()
            ->published()
            ->where('is_visible', true)
            ->get(['id', 'map_id', 'marker_type_id', 'name', 'x', 'y', 'geometry', 'floor', 'variant', 'status', 'confidence', 'confidence_override']);

        $user = $request->user();

        return Inertia::render('maps/Show', [
            'map' => new MapResource($map->load('gameVersion'))->resolve(),
            'maps' => MapSummaryResource::collection(Map::query()->published()->orderBy('sort_order')->orderBy('name')->get())->resolve(),
            'categories' => MarkerCategoryResource::collection(MarkerCategory::with('types')->orderBy('sort_order')->get())->resolve(),
            'markers' => MarkerResource::collection($markers)->resolve(),
            'reportTypes' => ReportType::options(),
            'focus' => $request->integer('marker') ?: null,
            'personal' => $user ? [
                'notes' => $user->mapNotes()->where('map_id', $map->id)->latest()->get(['id', 'x', 'y', 'title', 'body', 'color', 'is_shared']),
                'favorites' => $user->markerStates()->where('map_id', $map->id)->wherePivot('is_favorite', true)->pluck('markers.id'),
                'discovered' => $user->markerStates()->where('map_id', $map->id)->wherePivotNotNull('discovered_at')->pluck('markers.id'),
                'routes' => $user->raidRoutes()->where('map_id', $map->id)->latest()->get(['id', 'name', 'description', 'points', 'is_public', 'share_token']),
            ] : null,
            'sharedRoute' => $this->sharedRoute($request, $map),
            'canEdit' => (bool) $user?->canManageContent(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sharedRoute(Request $request, Map $map): ?array
    {
        $token = $request->string('route')->toString();
        if ($token === '') {
            return null;
        }

        $route = RaidRoute::query()
            ->where('map_id', $map->id)
            ->where('share_token', $token)
            ->where('is_public', true)
            ->with('user:id,name')
            ->first();

        return $route ? [
            'id' => $route->id,
            'name' => $route->name,
            'description' => $route->description,
            'points' => $route->points,
            'author' => $route->user->name,
        ] : null;
    }
}
