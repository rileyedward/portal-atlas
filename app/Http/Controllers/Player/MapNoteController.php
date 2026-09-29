<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\MapNoteRequest;
use App\Models\Map;
use App\Models\MapNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MapNoteController extends Controller
{
    public function store(MapNoteRequest $request): JsonResponse
    {
        $map = Map::findOrFail($request->integer('map_id'));
        Gate::authorize('view', $map);

        $note = $request->user()->mapNotes()->create([
            ...$request->safe()->only(['map_id', 'x', 'y', 'title', 'body', 'color', 'is_shared']),
            'share_token' => $request->boolean('is_shared') ? Str::random(24) : null,
        ]);

        return response()->json($this->present($note), 201);
    }

    public function update(MapNoteRequest $request, MapNote $note): JsonResponse
    {
        Gate::authorize('update', $note);

        $note->fill($request->safe()->only(['x', 'y', 'title', 'body', 'color', 'is_shared']));
        if ($note->is_shared && $note->share_token === null) {
            $note->share_token = Str::random(24);
        }
        $note->save();

        return response()->json($this->present($note));
    }

    public function destroy(MapNote $note): JsonResponse
    {
        Gate::authorize('delete', $note);
        $note->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(MapNote $note): array
    {
        return $note->only(['id', 'x', 'y', 'title', 'body', 'color', 'is_shared']);
    }
}
