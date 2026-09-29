<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Enums\MapStatus;
use App\Enums\ReportType;
use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Support\ConfidenceBreakdown;
use App\Support\Pivot;
use App\Support\SourceVisibility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObjectiveController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('objectives/Index', [
            'objectives' => Objective::query()->published()->with('map')->orderBy('name')->get()
                ->map(fn (Objective $o) => [
                    'slug' => $o->slug,
                    'name' => $o->name,
                    'kind' => $o->kind->label(),
                    'map' => $o->map?->status === MapStatus::Published ? $o->map->name : null,
                    'confidence' => $o->effectiveConfidence(),
                ]),
        ]);
    }

    public function show(Request $request, Objective $objective): Response
    {
        abort_unless($objective->status === ContentStatus::Published || $request->user()?->canManageContent(), 404);

        $objective->load([
            'map', 'source', 'verifiedVersion',
            'markers' => fn ($q) => $q->published()->where('is_visible', true)->with(['map', 'type']),
            'items' => fn ($q) => $q->published(),
        ]);
        $confidence = $objective->effectiveConfidence();

        return Inertia::render('objectives/Show', [
            'objective' => [
                'id' => $objective->id,
                'slug' => $objective->slug,
                'name' => $objective->name,
                'kind' => $objective->kind->label(),
                'description' => $objective->description,
                'giver' => $objective->giver,
                'rewards' => $objective->rewards,
                'map' => $objective->map?->status === MapStatus::Published ? ['slug' => $objective->map->slug, 'name' => $objective->map->name] : null,
                'confidence' => ['score' => $confidence, 'label' => ConfidenceBreakdown::labelFor($confidence)],
                ...(SourceVisibility::visibleTo($request->user()) ? [
                    'source' => $objective->source ? ['name' => $objective->source->name, 'url' => $objective->source_url ?? $objective->source->url] : null,
                    'source_url' => $objective->source_url,
                ] : []),
                'last_verified_at' => $objective->last_verified_at?->toIso8601String(),
                'verified_version' => $objective->verifiedVersion?->version,
            ],
            'markers' => $objective->markers
                ->filter(fn (Marker $m) => $m->map->status === MapStatus::Published)
                ->map(fn (Marker $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->type->name,
                    'role' => Pivot::get($m, 'role'),
                    'map' => ['slug' => $m->map->slug, 'name' => $m->map->name],
                ])->values(),
            'reportTypes' => ReportType::options(),
            'items' => $objective->items->map(fn (Item $i) => [
                'slug' => $i->slug,
                'name' => $i->name,
                'role' => Pivot::get($i, 'role'),
                'quantity' => Pivot::int($i, 'quantity'),
            ])->values(),
        ]);
    }
}
