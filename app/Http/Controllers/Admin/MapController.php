<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MapStatus;
use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Models\Map;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/maps/Index', [
            'maps' => Map::withCount('markers')->orderBy('sort_order')->orderBy('name')->get()->map(fn (Map $map) => [
                'id' => $map->id,
                'slug' => $map->slug,
                'name' => $map->name,
                'status' => $map->status->value,
                'markers_count' => $map->markers_count,
                'has_image' => $map->image_path !== null,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/maps/Form', $this->formProps(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $map = Map::create($this->validated($request));
        $this->storeImage($request, $map);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Map created.']);

        return to_route('admin.maps.edit', $map);
    }

    public function edit(Map $map): Response
    {

        return Inertia::render('admin/maps/Form', $this->formProps($map));
    }

    public function update(Request $request, Map $map): RedirectResponse
    {
        $map->update($this->validated($request, $map));
        $this->storeImage($request, $map);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Map saved.']);

        return to_route('admin.maps.edit', $map);
    }

    public function destroy(Map $map): RedirectResponse
    {
        abort_if($map->markers()->exists(), 422, 'Archive maps that still have markers instead of deleting them.');
        $map->deleteUploadedImage();
        $map->delete();

        return to_route('admin.maps.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Map $map): array
    {
        return [
            'map' => $map ? [...$map->toArray(), 'status' => $map->status->value, 'image_url' => $map->imageUrl()] : null,
            'statuses' => MapStatus::options(),
            'versions' => GameVersion::orderByDesc('released_at')->get(['id', 'version']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Map $map = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('maps')->ignore($map)],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(MapStatus::class)],
            'width' => ['required', 'integer', 'min:100', 'max:20000'],
            'height' => ['required', 'integer', 'min:100', 'max:20000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'game_version_id' => ['nullable', 'exists:game_versions,id'],
            'source_id' => ['nullable', 'exists:sources,id'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'image_attribution' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:20480'],
        ]);

        return Arr::except($data, ['image']);
    }

    private function storeImage(Request $request, Map $map): void
    {
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return;
        }

        $map->deleteUploadedImage();

        $path = $file->storeAs('maps', $map->slug.'-'.now()->timestamp.'.'.$file->extension(), Map::mediaDisk());
        $size = getimagesize($file->getRealPath());

        $map->update([
            'image_path' => $path,
            'width' => $size[0] ?? $map->width,
            'height' => $size[1] ?? $map->height,
        ]);
    }
}
