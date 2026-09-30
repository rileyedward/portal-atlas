<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarkerDetailResource;
use App\Models\Marker;
use App\Services\Analytics;
use App\Support\PublicCache;
use App\Support\SourceVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarkerDetailController extends Controller
{
    public function __invoke(Request $request, Marker $marker, Analytics $analytics, PublicCache $cache): JsonResponse|MarkerDetailResource
    {
        Gate::authorize('view', $marker);

        $analytics->record('marker_open', 'marker', $marker->id);

        // Editors see provenance, so only the player view is shared.
        if (! SourceVisibility::visibleTo($request->user())) {
            return response()->json([
                'data' => $cache->remember("marker.{$marker->id}.detail", fn () => $this->resource($marker)->resolve($request)),
            ]);
        }

        return $this->resource($marker);
    }

    private function resource(Marker $marker): MarkerDetailResource
    {
        $marker->load([
            'map', 'type.category', 'source', 'introducedVersion', 'verifiedVersion',
            'lootTable.items' => fn ($q) => $q->published(),
            'items' => fn ($q) => $q->published()->orderBy('name'),
            'objectives' => fn ($q) => $q->published()->orderBy('name'),
        ]);

        return new MarkerDetailResource($marker);
    }
}
