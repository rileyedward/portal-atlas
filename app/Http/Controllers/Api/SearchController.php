<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Services\Analytics;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search, Analytics $analytics): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'map' => ['nullable', 'string', 'max:100'],
        ]);

        $scope = isset($validated['map']) ? Map::query()->published()->where('slug', $validated['map'])->first() : null;
        $results = $search->search($validated['q'], $scope);

        $analytics->record('search', term: $results['query'], resultCount: $results['total']);

        return response()->json($results);
    }
}
