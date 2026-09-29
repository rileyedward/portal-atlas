<?php

namespace App\Http\Controllers\Player;

use App\Enums\ItemIntent;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Recipe;
use App\Services\KeepAdvisor;
use App\Support\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrackedItemController extends Controller
{
    public function index(Request $request): Response
    {
        $tracked = $request->user()->trackedItems()->with('category')->orderBy('name')->get();

        return Inertia::render('player/Items', [
            'tracked' => $tracked->map(fn (Item $item) => [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'category' => $item->category?->name,
                'intent' => Pivot::get($item, 'intent'),
                'quantity_needed' => Pivot::int($item, 'quantity_needed'),
                'quantity_owned' => Pivot::int($item, 'quantity_owned'),
                'is_favorite' => (bool) Pivot::get($item, 'is_favorite'),
                'note' => Pivot::get($item, 'note'),
            ])->values(),
            'goals' => Recipe::query()->published()->with('ingredients:id,name,slug')->orderBy('kind')->orderBy('name')->get()
                ->map(fn (Recipe $recipe) => [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'kind' => $recipe->kind->label(),
                    'ingredients' => $recipe->ingredients->map(fn (Item $i) => ['id' => $i->id, 'name' => $i->name, 'quantity' => Pivot::int($i, 'quantity')])->values(),
                ]),
            'intents' => ItemIntent::options(),
        ]);
    }

    public function update(Request $request, Item $item, KeepAdvisor $advisor): JsonResponse
    {
        $validated = $request->validate([
            'intent' => ['nullable', Rule::enum(ItemIntent::class)],
            'quantity_needed' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'quantity_owned' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_favorite' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->trackedItems()->syncWithoutDetaching([$item->id => $validated]);

        return response()->json(['advice' => $advisor->advise($item, $user)]);
    }

    public function destroy(Request $request, Item $item): JsonResponse
    {
        $request->user()->trackedItems()->detach($item->id);

        return response()->json(null, 204);
    }

    /**
     * Adds every ingredient of a recipe/upgrade goal to the player's needs.
     */
    public function addGoal(Request $request, Recipe $recipe): JsonResponse
    {
        $user = $request->user();
        $recipe->load('ingredients');

        foreach ($recipe->ingredients as $ingredient) {
            $existing = $user->trackedItems()->whereKey($ingredient->id)->first()?->pivot;
            $user->trackedItems()->syncWithoutDetaching([$ingredient->id => [
                'intent' => ItemIntent::Need->value,
                'quantity_needed' => (int) $existing?->getAttribute('quantity_needed') + Pivot::int($ingredient, 'quantity'),
            ]]);
        }

        return response()->json(['message' => "Added {$recipe->ingredients->count()} items from {$recipe->name}."]);
    }
}
