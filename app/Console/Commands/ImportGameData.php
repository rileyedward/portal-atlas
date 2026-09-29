<?php

namespace App\Console\Commands;

use App\Services\DataExchange\GameDataImporter;
use App\Services\DataExchange\MapDatasetImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game-data:import {path : JSON file (active-matter-data/v1 or active-matter-map/v1)} {--dry-run : Validate without saving}')]
#[Description('Import reference game data or a map marker dataset from JSON')]
class ImportGameData extends Command
{
    public function handle(GameDataImporter $gameData, MapDatasetImporter $mapData): int
    {
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            $this->error('Invalid JSON.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = ($payload['format'] ?? null) === GameDataImporter::FORMAT
            ? $gameData->import($payload, $dryRun)
            : $mapData->import($payload, $dryRun);

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }
        foreach ($result->errors as $error) {
            $this->error($error);
        }

        $this->info(sprintf('%s created: %d, updated: %d, unchanged: %d', $dryRun ? '[dry run]' : '', $result->created, $result->updated, $result->unchanged));

        return $result->ok() ? self::SUCCESS : self::FAILURE;
    }
}
