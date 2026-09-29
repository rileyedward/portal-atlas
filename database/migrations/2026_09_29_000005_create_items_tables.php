<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rarity', 20)->nullable();
            $table->unsignedInteger('value')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('icon_path')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_url')->nullable();
            $table->text('source_note')->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedTinyInteger('confidence_override')->nullable();
            $table->unsignedInteger('confirmations_count')->default(0);
            $table->unsignedInteger('open_reports_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('introduced_version_id')->nullable()->constrained('game_versions')->nullOnDelete();
            $table->foreignId('verified_version_id')->nullable()->constrained('game_versions')->nullOnDelete();
            $table->timestamps();
        });

        // "Found at": which markers are known to contain an item.
        Schema::create('item_marker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marker_id')->constrained()->cascadeOnDelete();
            $table->string('likelihood', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'marker_id']);
        });

        // Crafting recipes and upgrades share one shape: a target plus a list of item inputs.
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('station')->nullable();
            $table->unsignedSmallInteger('level')->nullable();
            $table->foreignId('output_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedInteger('output_quantity')->default(1);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_url')->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->foreignId('verified_version_id')->nullable()->constrained('game_versions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['recipe_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('item_marker');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
    }
};
