<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Services\DataExchange\GameDataImporter;
use App\Services\DataExchange\MapDatasetExporter;
use App\Services\DataExchange\MapDatasetImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class DataExchangeController extends Controller
{
    public function index(): Response
    {

        return Inertia::render('admin/DataExchange', [
            'maps' => Map::orderBy('name')->get(['id', 'slug', 'name']),
        ]);
    }

    public function export(Map $map, MapDatasetExporter $exporter): JsonResponse
    {
        return response()->json($exporter->export($map), 200, [
            'Content-Disposition' => 'attachment; filename="'.$map->slug.'-markers.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function import(Request $request, MapDatasetImporter $maps, GameDataImporter $gameData): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:10240'],
            'dry_run' => ['boolean'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $payload = json_decode((string) file_get_contents($file->getRealPath()), true);

        if (! is_array($payload)) {
            Inertia::flash('importResult', ['dry_run' => true, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => ['The file is not valid JSON.'], 'warnings' => []]);

            return back();
        }

        $dryRun = $request->boolean('dry_run', true);
        $result = ($payload['format'] ?? null) === GameDataImporter::FORMAT
            ? $gameData->import($payload, $dryRun)
            : $maps->import($payload, $dryRun, $request->user());

        Inertia::flash('importResult', $result->toArray());

        return back();
    }
}
