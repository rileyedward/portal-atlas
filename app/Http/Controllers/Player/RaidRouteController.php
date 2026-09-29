<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\RaidRouteRequest;
use App\Models\Map;
use App\Models\RaidRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RaidRouteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('player/Routes', [
            'routes' => $request->user()->raidRoutes()->with('map:id,slug,name')->latest()->get()
                ->map(fn (RaidRoute $route) => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'stops' => count($route->points),
                    'is_public' => $route->is_public,
                    'share_token' => $route->share_token,
                    'map' => ['slug' => $route->map->slug, 'name' => $route->map->name],
                    'updated_at' => $route->updated_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(RaidRouteRequest $request): JsonResponse
    {
        Gate::authorize('view', Map::findOrFail($request->integer('map_id')));

        $route = $request->user()->raidRoutes()->create([
            'map_id' => $request->integer('map_id'),
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'points' => $request->points(),
            'is_public' => $request->boolean('is_public'),
            'share_token' => Str::random(24),
        ]);

        return response()->json($this->present($route), 201);
    }

    public function update(RaidRouteRequest $request, RaidRoute $route): JsonResponse
    {
        Gate::authorize('update', $route);

        $route->fill($request->safe()->only(['name', 'description', 'is_public']));
        if ($request->has('points')) {
            $route->points = $request->points();
        }
        $route->save();

        return response()->json($this->present($route));
    }

    public function destroy(RaidRoute $route): JsonResponse
    {
        Gate::authorize('delete', $route);
        $route->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(RaidRoute $route): array
    {
        return $route->only(['id', 'name', 'description', 'points', 'is_public', 'share_token']);
    }
}
