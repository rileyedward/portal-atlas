<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Community\RecalculateConfidence;
use App\Enums\SourceKind;
use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SourceController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/sources/Index', [
            'sources' => Source::orderBy('name')->get()->map(fn (Source $s) => [...$s->toArray(), 'kind' => $s->kind->value]),
            'kinds' => SourceKind::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Source::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Source added.']);

        return back();
    }

    public function update(Request $request, Source $source, RecalculateConfidence $recalculate): RedirectResponse
    {
        $source->update($this->validated($request, $source));

        if ($source->wasChanged('reliability')) {
            $recalculate->handle($source->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Source saved.']);

        return back();
    }

    public function destroy(Source $source): RedirectResponse
    {
        $source->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Source $source = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sources')->ignore($source)],
            'kind' => ['required', Rule::enum(SourceKind::class)],
            'url' => ['nullable', 'url', 'max:255'],
            'reliability' => ['required', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
