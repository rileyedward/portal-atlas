<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\ObjectiveKind;
use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Map;
use App\Models\Objective;
use App\Models\Source;
use App\Services\ConfidenceCalculator;
use App\Support\Pivot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ObjectiveController extends Controller
{
    public function __construct(private ConfidenceCalculator $confidence) {}

    public function index(): Response
    {

        return Inertia::render('admin/objectives/Index', [
            'objectives' => Objective::with('map')->withCount(['markers', 'items'])->orderBy('name')->get()
                ->map(fn (Objective $o) => [
                    'id' => $o->id,
                    'slug' => $o->slug,
                    'name' => $o->name,
                    'kind' => $o->kind->label(),
                    'map' => $o->map?->name,
                    'status' => $o->status->value,
                    'confidence' => $o->effectiveConfidence(),
                    'markers_count' => $o->markers_count,
                    'items_count' => $o->items_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/objectives/Form', $this->formProps(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $objective = Objective::create($this->validated($request));
        $this->syncItems($request, $objective);
        $this->confidence->refresh($objective);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Objective created.']);

        return to_route('admin.objectives.edit', $objective);
    }

    public function edit(Objective $objective): Response
    {

        return Inertia::render('admin/objectives/Form', $this->formProps($objective));
    }

    public function update(Request $request, Objective $objective): RedirectResponse
    {
        $objective->update($this->validated($request, $objective));
        $this->syncItems($request, $objective);
        $this->confidence->refresh($objective);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Objective saved.']);

        return to_route('admin.objectives.edit', $objective);
    }

    public function destroy(Objective $objective): RedirectResponse
    {
        $objective->delete();

        return to_route('admin.objectives.index');
    }

    private function syncItems(Request $request, Objective $objective): void
    {
        /** @var list<array{id: int, quantity: int, role: string}> $items */
        $items = $request->validate([
            'items' => ['array'],
            'items.*.id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.role' => ['required', Rule::in(['required', 'reward'])],
        ])['items'] ?? [];

        $objective->items()->detach();
        foreach ($items as $row) {
            $objective->items()->attach($row['id'], ['quantity' => $row['quantity'], 'role' => $row['role']]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Objective $objective): array
    {
        $objective?->load(['items', 'markers.map:id,name,slug']);

        return [
            'objective' => $objective ? [
                ...$objective->only(['id', 'slug', 'name', 'description', 'map_id', 'giver', 'rewards', 'source_id', 'source_url', 'source_note', 'confidence', 'confidence_override', 'introduced_version_id', 'verified_version_id']),
                'kind' => $objective->kind->value,
                'status' => $objective->status->value,
                'items' => $objective->items->map(fn (Item $i) => ['id' => $i->id, 'quantity' => Pivot::int($i, 'quantity'), 'role' => Pivot::get($i, 'role')])->values(),
                'markers' => $objective->markers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'map' => $m->map->name, 'map_slug' => $m->map->slug])->values(),
            ] : null,
            'kinds' => ObjectiveKind::options(),
            'statuses' => ContentStatus::options(),
            'maps' => Map::orderBy('name')->get(['id', 'name']),
            'items' => Item::orderBy('name')->get(['id', 'name']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'versions' => GameVersion::orderByDesc('released_at')->get(['id', 'version']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Objective $objective = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('objectives')->ignore($objective)],
            'kind' => ['required', Rule::enum(ObjectiveKind::class)],
            'map_id' => ['nullable', 'exists:maps,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'giver' => ['nullable', 'string', 'max:255'],
            'rewards' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'source_id' => ['nullable', 'exists:sources,id'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'source_note' => ['nullable', 'string', 'max:2000'],
            'confidence_override' => ['nullable', 'integer', 'between:0,100'],
            'introduced_version_id' => ['nullable', 'exists:game_versions,id'],
            'verified_version_id' => ['nullable', 'exists:game_versions,id'],
        ]);
    }
}
