<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\Marker;
use App\Support\ConfidenceBreakdown;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExtractionController extends Controller
{
    public function show(Map $map): Response
    {
        Gate::authorize('view', $map);

        $extracts = $map->markers()->published()->where('is_visible', true)
            ->whereHas('type.category', fn ($q) => $q->where('slug', 'extraction'))
            ->with(['type', 'verifiedVersion'])
            ->orderBy('name')
            ->get();

        return Inertia::render('maps/Extractions', [
            'map' => ['slug' => $map->slug, 'name' => $map->name],
            'extracts' => $extracts->map(fn (Marker $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'type' => $m->type->name,
                'description' => $m->description,
                'conditions' => $m->metadata['conditions'] ?? null,
                'confidence' => ['score' => $m->effectiveConfidence(), 'label' => ConfidenceBreakdown::labelFor($m->effectiveConfidence())],
                'verified_version' => $m->verifiedVersion?->version,
            ])->values(),
        ]);
    }
}
