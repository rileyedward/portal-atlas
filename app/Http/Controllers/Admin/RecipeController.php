<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\RecipeKind;
use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Recipe;
use App\Models\Source;
use App\Support\Pivot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/recipes/Index', [
            'recipes' => Recipe::with('outputItem')->withCount('ingredients')->orderBy('kind')->orderBy('name')->get()
                ->map(fn (Recipe $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'kind' => $r->kind->label(),
                    'station' => $r->station,
                    'level' => $r->level,
                    'output' => $r->outputItem?->name,
                    'ingredients_count' => $r->ingredients_count,
                    'status' => $r->status->value,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/recipes/Form', $this->formProps(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $recipe = Recipe::create($this->validated($request));
        $this->syncIngredients($request, $recipe);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recipe created.']);

        return to_route('admin.recipes.edit', $recipe);
    }

    public function edit(Recipe $recipe): Response
    {

        return Inertia::render('admin/recipes/Form', $this->formProps($recipe));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $recipe->update($this->validated($request, $recipe));
        $this->syncIngredients($request, $recipe);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recipe saved.']);

        return to_route('admin.recipes.edit', $recipe);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return to_route('admin.recipes.index');
    }

    private function syncIngredients(Request $request, Recipe $recipe): void
    {
        /** @var list<array{id: int, quantity: int}> $rows */
        $rows = $request->validate([
            'ingredients' => ['array'],
            'ingredients.*.id' => ['required', 'distinct', 'exists:items,id'],
            'ingredients.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ])['ingredients'] ?? [];

        $recipe->ingredients()->sync(collect($rows)->mapWithKeys(fn (array $row) => [$row['id'] => ['quantity' => $row['quantity']]])->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Recipe $recipe): array
    {
        $recipe?->load('ingredients');

        return [
            'recipe' => $recipe ? [
                ...$recipe->only(['id', 'slug', 'name', 'station', 'level', 'output_item_id', 'output_quantity', 'description', 'source_id', 'source_url', 'confidence', 'verified_version_id']),
                'kind' => $recipe->kind->value,
                'status' => $recipe->status->value,
                'ingredients' => $recipe->ingredients->map(fn (Item $i) => ['id' => $i->id, 'quantity' => Pivot::int($i, 'quantity')])->values(),
            ] : null,
            'kinds' => RecipeKind::options(),
            'statuses' => ContentStatus::options(),
            'items' => Item::orderBy('name')->get(['id', 'name']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'versions' => GameVersion::orderByDesc('released_at')->get(['id', 'version']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Recipe $recipe = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('recipes')->ignore($recipe)],
            'kind' => ['required', Rule::enum(RecipeKind::class)],
            'station' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'output_item_id' => ['nullable', 'exists:items,id'],
            'output_quantity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'source_id' => ['nullable', 'exists:sources,id'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'confidence' => ['sometimes', 'integer', 'between:0,100'],
            'verified_version_id' => ['nullable', 'exists:game_versions,id'],
        ]);
    }
}
