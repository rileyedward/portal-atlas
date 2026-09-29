<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marker_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('color', 20);
            $table->string('icon', 50);
            $table->boolean('visible_by_default')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marker_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marker_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('geometry', 20)->default('point');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marker_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // Normalized coordinates: percent of map width/height, origin top-left.
            // Null = the place is known from a source but not yet positioned on the map.
            $table->decimal('x', 7, 4)->nullable();
            $table->decimal('y', 7, 4)->nullable();
            // Optional polygon/polyline points (array of [x, y] in the same space).
            $table->jsonb('geometry')->nullable();
            $table->string('floor', 50)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->boolean('is_visible')->default(true);
            $table->jsonb('metadata')->nullable();
            // Data quality.
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
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['map_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markers');
        Schema::dropIfExists('marker_types');
        Schema::dropIfExists('marker_categories');
    }
};
