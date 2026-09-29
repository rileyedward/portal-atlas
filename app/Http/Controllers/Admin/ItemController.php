<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Community\ConfirmAccuracy;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Source;
use App\Services\ConfidenceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function __construct(private ConfidenceCalculator $confidence) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/items/Index', [
            'items' => Item::with('category')->withCount(['markers', 'usedInRecipes', 'objectives'])
                ->when($search !== '', fn ($q) => $q->whereLike('name', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy('name')->paginate(50)->withQueryString()
                ->through(fn (Item $item) => [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'name' => $item->name,
                    'category' => $item->category?->name,
                    'rarity' => $item->rarity,
                    'status' => $item->status->value,
                    'confidence' => $item->effectiveConfidence(),
                    'markers_count' => $item->markers_count,
                    'uses_count' => $item->used_in_recipes_count + $item->objectives_count,
                ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/items/Form', $this->formProps(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Item::create($this->validated($request));
        $this->confidence->refresh($item);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item created.']);

        return to_route('admin.items.edit', $item);
    }

    public function edit(Item $item): Response
    {

        return Inertia::render('admin/items/Form', $this->formProps($item));
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $item->update($this->validated($request, $item));
        $this->confidence->refresh($item);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item saved.']);

        return to_route('admin.items.edit', $item);
    }

    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item deleted.']);

        return to_route('admin.items.index');
    }

    public function verify(Request $request, Item $item, ConfirmAccuracy $confirm): RedirectResponse
    {
        $confirm->handle($item, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marked as verified for the current version.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Item $item): array
    {
        $item?->load(['markers.map:id,name', 'usedInRecipes', 'objectives']);

        return [
            'item' => $item ? [
                ...$item->only(['id', 'slug', 'name', 'description', 'item_category_id', 'rarity', 'value', 'weight', 'source_id', 'source_url', 'source_note', 'confidence', 'confidence_override', 'introduced_version_id', 'verified_version_id']),
                'status' => $item->status->value,
                'last_verified_at' => $item->last_verified_at?->toIso8601String(),
                'markers' => $item->markers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'map' => $m->map->name, 'map_slug' => $m->map->slug]),
                'recipes' => $item->usedInRecipes->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
                'objectives' => $item->objectives->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'slug' => $o->slug]),
            ] : null,
            'categories' => ItemCategory::orderBy('name')->get(['id', 'name']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'versions' => GameVersion::orderByDesc('released_at')->get(['id', 'version']),
            'statuses' => ContentStatus::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Item $item = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('items')->ignore($item)],
            'description' => ['nullable', 'string', 'max:5000'],
            'item_category_id' => ['nullable', 'exists:item_categories,id'],
            'rarity' => ['nullable', 'string', 'max:20'],
            'value' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:99999'],
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
