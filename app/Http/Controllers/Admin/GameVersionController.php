<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Community\RecalculateConfidence;
use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameVersionController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/versions/Index', [
            'versions' => GameVersion::orderByDesc('released_at')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save(new GameVersion, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Version added.']);

        return back();
    }

    public function update(Request $request, GameVersion $version): RedirectResponse
    {
        $this->save($version, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Version saved.']);

        return back();
    }

    public function destroy(GameVersion $version): RedirectResponse
    {
        $version->delete();

        return back();
    }

    private function save(GameVersion $version, Request $request): void
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:50', Rule::unique('game_versions')->ignore($version)],
            'name' => ['nullable', 'string', 'max:255'],
            'released_at' => ['nullable', 'date'],
            'is_current' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url', 'max:255'],
        ]);

        DB::transaction(function () use ($version, $data) {
            if (! empty($data['is_current'])) {
                GameVersion::query()->whereKeyNot($version->id)->update(['is_current' => false]);
            }
            $version->fill($data)->save();
        });

        // A new current build makes everything verified on older builds stale.
        if ($version->wasChanged('is_current') || $version->wasRecentlyCreated && $version->is_current) {
            app(RecalculateConfidence::class)->handle();
        }
    }
}
