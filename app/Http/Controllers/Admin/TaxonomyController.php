<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarkerGeometry;
use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\MarkerCategory;
use App\Models\MarkerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manage marker categories/types and item categories without code changes.
 */
class TaxonomyController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/taxonomy/Index', [
            'categories' => MarkerCategory::with('types')->withCount('types')->orderBy('sort_order')->get(),
            'itemCategories' => ItemCategory::withCount('items')->orderBy('sort_order')->orderBy('name')->get(),
            'geometries' => MarkerGeometry::options(),
        ]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        MarkerType::create($this->validatedType($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marker type added.']);

        return back();
    }

    public function updateType(Request $request, MarkerType $type): RedirectResponse
    {
        $type->update($this->validatedType($request, $type));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marker type saved.']);

        return back();
    }

    public function destroyType(MarkerType $type): RedirectResponse
    {
        abort_if($type->markers()->withTrashed()->exists(), 422, 'This type is still used by markers.');
        $type->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marker type deleted.']);

        return back();
    }

    public function updateCategory(Request $request, MarkerCategory $category): RedirectResponse
    {
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', 'string', 'max:50'],
            'visible_by_default' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category saved.']);

        return back();
    }

    public function storeItemCategory(Request $request): RedirectResponse
    {
        ItemCategory::create($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', 'unique:item_categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item category added.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedType(Request $request, ?MarkerType $type = null): array
    {
        return $request->validate([
            'marker_category_id' => ['required', 'exists:marker_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('marker_types')->ignore($type)],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'geometry' => ['required', Rule::enum(MarkerGeometry::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }
}
