<?php

use Database\Seeders\GameDataSeeder;
use Database\Seeders\MarkerTaxonomySeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Loads the reference dataset on deploy so `php artisan migrate --force` gives
 * a fully populated site: marker taxonomy, versions, maps (with base images),
 * items, loot pools, objectives and markers.
 *
 * The seeders upsert by natural keys, so running them again is safe. To ship
 * a data update later, regenerate database/data and add a new migration that
 * calls GameDataSeeder again.
 *
 * Skipped in the test environment, where each test builds its own data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        app(MarkerTaxonomySeeder::class)->setContainer(app())->__invoke();
        app(GameDataSeeder::class)->setContainer(app())->__invoke();
    }

    public function down(): void
    {
        // Reference data is left in place; rolling back schema migrations removes it.
    }
};
