<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named loot pools (e.g. "Tier 2 box", "Changed drop"). Markers point at a
        // table; the table lists items with drop chances.
        Schema::create('loot_tables', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('item_loot_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loot_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            // Drop chance in percent (0-100) when known.
            $table->decimal('chance', 6, 3)->nullable();
            $table->timestamps();

            $table->unique(['loot_table_id', 'item_id']);
            $table->index('item_id');
        });

        Schema::table('markers', function (Blueprint $table) {
            // Raid variant this marker belongs to (null = present in every variant).
            $table->string('variant', 40)->nullable()->after('floor')->index();
            $table->foreignId('loot_table_id')->nullable()->after('variant')->constrained()->nullOnDelete();
            // Stable id from an external dataset so re-imports update in place.
            $table->string('external_ref', 120)->nullable()->after('loot_table_id');
            $table->unique(['map_id', 'external_ref']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('external_ref', 160)->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['external_ref']);
            $table->dropColumn('external_ref');
        });
        Schema::table('markers', function (Blueprint $table) {
            $table->dropUnique(['map_id', 'external_ref']);
            $table->dropConstrainedForeignId('loot_table_id');
            $table->dropColumn(['variant', 'external_ref']);
        });
        Schema::dropIfExists('item_loot_table');
        Schema::dropIfExists('loot_tables');
    }
};
