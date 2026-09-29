<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Per-user marker state: favorites and "discovered" tracking.
 */
class MarkerStateController extends Controller
{
    public function update(Request $request, Marker $marker): JsonResponse
    {
        Gate::authorize('view', $marker);

        $validated = $request->validate([
            'is_favorite' => ['sometimes', 'boolean'],
            'discovered' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $existing = $user->markerStates()->whereKey($marker->id)->first()?->pivot;

        $attributes = [
            'is_favorite' => $validated['is_favorite'] ?? (bool) $existing?->getAttribute('is_favorite'),
            'discovered_at' => array_key_exists('discovered', $validated)
                ? ($validated['discovered'] ? now() : null)
                : $existing?->getAttribute('discovered_at'),
        ];

        $user->markerStates()->syncWithoutDetaching([$marker->id => $attributes]);

        return response()->json([
            'marker_id' => $marker->id,
            'is_favorite' => $attributes['is_favorite'],
            'discovered' => $attributes['discovered_at'] !== null,
        ]);
    }
}
