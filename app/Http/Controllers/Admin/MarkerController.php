<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Community\ConfirmAccuracy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkerRequest;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Services\ConfidenceCalculator;
use App\Support\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints used by the admin map editor.
 */
class MarkerController extends Controller
{
    public function __construct(private ConfidenceCalculator $confidence) {}

    public function show(Map $map, Marker $marker): JsonResponse
    {
        abort_unless($marker->map_id === $map->id, 404);

        return response()->json($this->present($marker));
    }

    public function store(MarkerRequest $request, Map $map): JsonResponse
    {
        $marker = $map->markers()->create([
            ...$request->markerAttributes(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);
        $this->syncRelations($request, $marker);
        $this->confidence->refresh($marker);

        return response()->json($this->present($marker), 201);
    }

    public function update(MarkerRequest $request, Map $map, Marker $marker): JsonResponse
    {
        abort_unless($marker->map_id === $map->id, 404);

        $marker->update([...$request->markerAttributes(), 'updated_by' => $request->user()?->id]);
        $this->syncRelations($request, $marker);
        $this->confidence->refresh($marker);

        return response()->json($this->present($marker));
    }

    public function duplicate(Request $request, Map $map, Marker $marker): JsonResponse
    {
        abort_unless($marker->map_id === $map->id, 404);

        $copy = $marker->replicate(['confidence', 'confirmations_count', 'open_reports_count', 'last_verified_at', 'verified_version_id']);
        $copy->name = $marker->name.' (copy)';
        $copy->x = $marker->x !== null ? min(100, $marker->x + 1.5) : null;
        $copy->y = $marker->y !== null ? min(100, $marker->y + 1.5) : null;
        $copy->created_by = $request->user()?->id;
        $copy->save();
        $copy->items()->sync($marker->items()->get()->mapWithKeys(fn (Item $i) => [$i->id => ['likelihood' => Pivot::get($i, 'likelihood'), 'note' => Pivot::get($i, 'note')]])->all());
        $this->confidence->refresh($copy);

        return response()->json($this->present($copy), 201);
    }

    public function destroy(Map $map, Marker $marker): JsonResponse
    {
        abort_unless($marker->map_id === $map->id, 404);
        $marker->delete();

        return response()->json(null, 204);
    }

    public function verify(Request $request, Map $map, Marker $marker, ConfirmAccuracy $confirm): JsonResponse
    {
        abort_unless($marker->map_id === $map->id, 404);
        $confirm->handle($marker, $request->user());

        return response()->json($this->present($marker->refresh()));
    }

    private function syncRelations(MarkerRequest $request, Marker $marker): void
    {
        if ($request->has('items')) {
            /** @var list<array{id: int, likelihood?: string|null, note?: string|null}> $items */
            $items = $request->validated('items');
            $marker->items()->sync(collect($items)->mapWithKeys(fn (array $i) => [$i['id'] => [
                'likelihood' => $i['likelihood'] ?? null,
                'note' => $i['note'] ?? null,
            ]])->all());
        }
        if ($request->has('objectives')) {
            /** @var list<array{id: int, role?: string|null}> $objectives */
            $objectives = $request->validated('objectives');
            $marker->objectives()->sync(collect($objectives)->mapWithKeys(fn (array $o) => [$o['id'] => ['role' => $o['role'] ?? null]])->all());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Marker $marker): array
    {
        $marker->loadMissing(['items', 'objectives']);

        return [
            ...$marker->only([
                'id', 'marker_type_id', 'name', 'description', 'x', 'y', 'geometry', 'floor', 'variant', 'loot_table_id', 'is_visible',
                'source_id', 'source_url', 'source_note', 'confidence', 'confidence_override',
                'confirmations_count', 'open_reports_count', 'introduced_version_id', 'verified_version_id',
            ]),
            'status' => $marker->status->value,
            'metadata' => $marker->metadata ?? (object) [],
            'last_verified_at' => $marker->last_verified_at?->toIso8601String(),
            'items' => $marker->items->map(fn (Item $i) => [
                'id' => $i->id, 'name' => $i->name,
                'likelihood' => Pivot::get($i, 'likelihood'), 'note' => Pivot::get($i, 'note'),
            ])->values(),
            'objectives' => $marker->objectives->map(fn (Objective $o) => [
                'id' => $o->id, 'name' => $o->name, 'role' => Pivot::get($o, 'role'),
            ])->values(),
        ];
    }
}
