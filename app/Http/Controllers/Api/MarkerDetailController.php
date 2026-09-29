<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarkerDetailResource;
use App\Models\Marker;
use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarkerDetailController extends Controller
{
    public function __invoke(Request $request, Marker $marker, Analytics $analytics): MarkerDetailResource
    {
        Gate::authorize('view', $marker);

        $analytics->record('marker_open', 'marker', $marker->id);

        $marker->load([
            'map', 'type.category', 'source', 'introducedVersion', 'verifiedVersion',
            'lootTable.items' => fn ($q) => $q->published(),
            'items' => fn ($q) => $q->published()->orderBy('name'),
            'objectives' => fn ($q) => $q->published()->orderBy('name'),
        ]);

        return new MarkerDetailResource($marker);
    }
}
