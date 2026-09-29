<?php

namespace Database\Seeders;

use App\Models\Map;
use App\Services\DataExchange\GameDataImporter;
use App\Services\DataExchange\ImportResult;
use App\Services\DataExchange\MapDatasetImporter;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Loads the sourced reference dataset from database/data. Every record in
 * those files cites its source; see docs/data-import.md for how they are built.
 */
class GameDataSeeder extends Seeder
{
    /** False when run from a migration, where there is no console to print to. */
    private bool $hasCommand = false;

    public function run(GameDataImporter $gameData, MapDatasetImporter $maps): void
    {
        $dir = database_path('data');

        foreach (['reference.json', 'items.json', 'objectives.json'] as $file) {
            $this->check($file, $gameData->import($this->read("{$dir}/{$file}"), dryRun: false));
        }

        foreach (glob("{$dir}/maps/*.json") ?: [] as $path) {
            $this->check(basename($path), $maps->import($this->read($path), dryRun: false));
        }

        // Positioned markers, zones and loot tables from activematterhelp.ru
        // (see database/data/tools/build_activematterhelp.py).
        $amh = "{$dir}/activematterhelp";
        if (is_file("{$amh}/reference.json")) {
            $this->check('activematterhelp/reference.json', $gameData->import($this->read("{$amh}/reference.json"), dryRun: false));

            foreach (glob("{$amh}/maps/*.json") ?: [] as $path) {
                $this->check('activematterhelp/'.basename($path), $maps->import($this->read($path), dryRun: false));
            }

            $this->attachMapImages("{$amh}/map-images.json");
        }
    }

    /**
     * Attach base images built by database/data/tools/build_map_images.py.
     * Images live on the public disk (not in git); missing files are skipped.
     */
    private function attachMapImages(string $manifestPath): void
    {
        if (! is_file($manifestPath)) {
            return;
        }

        /** @var list<array{slug: string, path: string, width: int, height: int, attribution: string}> $entries */
        $entries = $this->read($manifestPath);
        $attached = 0;

        foreach ($entries as $entry) {
            if (! is_file(public_path($entry['path']))) {
                continue;
            }

            $attached += Map::where('slug', $entry['slug'])->update([
                'image_path' => $entry['path'],
                'image_attribution' => $entry['attribution'],
                'width' => $entry['width'],
                'height' => $entry['height'],
            ]);
        }

        $this->say("  map images: {$attached} attached");
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $path): array
    {
        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            throw new RuntimeException("Invalid JSON in {$path}");
        }

        return $payload;
    }

    private function check(string $file, ImportResult $result): void
    {
        if (! $result->ok()) {
            throw new RuntimeException("Import of {$file} failed: ".implode(' | ', $result->errors));
        }

        $this->say(sprintf('  %s: %d created, %d updated, %d unchanged', $file, $result->created, $result->updated, $result->unchanged));
    }

    /**
     * Output progress when run from artisan; stay quiet inside a migration.
     */
    private function say(string $message): void
    {
        if ($this->hasCommand) {
            $this->command->info($message);
        }
    }

    public function setCommand(Command $command)
    {
        $this->hasCommand = true;

        return parent::setCommand($command);
    }
}
