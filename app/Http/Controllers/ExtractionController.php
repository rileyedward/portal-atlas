<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\Marker;
use App\Support\ConfidenceBreakdown;
use App\Support\PublicCache;
use App\Support\Seo;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExtractionController extends Controller
{
    public function show(Map $map, PublicCache $cache): Response
    {
        Gate::authorize('view', $map);

        return Inertia::render('maps/Extractions', [
            'seo' => Seo::make(
                "{$map->name} Extraction Points – Active Matter",
                "Documented extraction points on {$map->name} in Active Matter: type, conditions, confidence and the game version each was verified for.",
            ),
            'map' => ['slug' => $map->slug, 'name' => $map->name],
            'extracts' => $cache->remember("map.{$map->id}.extractions", fn () => $map->markers()->published()->where('is_visible', true)
                ->whereHas('type.category', fn ($q) => $q->where('slug', 'extraction'))
                ->with(['type', 'verifiedVersion'])
                ->orderBy('name')
                ->get()
                ->map(fn (Marker $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->type->name,
                    'description' => $m->description,
                    'conditions' => $m->metadata['conditions'] ?? null,
                    'confidence' => ['score' => $m->effectiveConfidence(), 'label' => ConfidenceBreakdown::labelFor($m->effectiveConfidence())],
                    'verified_version' => $m->verifiedVersion?->version,
                ])->values()->all()),
        ]);
    }
}
