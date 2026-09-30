<?php

namespace App\Http\Controllers;

use App\Http\Resources\MapSummaryResource;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Support\PublicCache;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(PublicCache $cache): Response
    {
        return Inertia::render('Home', [
            'seo' => Seo::make('Active Matter Interactive Map & Raid Companion'),
            ...$cache->remember('home', function () {
                $maps = Map::query()->published()
                    ->withCount(['markers' => fn ($q) => $q->published()])
                    ->orderBy('sort_order')->orderBy('name')->get();

                return [
                    'maps' => MapSummaryResource::collection($maps)->resolve(),
                    'currentVersion' => GameVersion::current()?->label(),
                    'stats' => [
                        'maps' => $maps->count(),
                        'markers' => Marker::query()->published()->count(),
                        'items' => Item::query()->published()->count(),
                        'objectives' => Objective::query()->published()->count(),
                    ],
                ];
            }),
        ]);
    }
}
