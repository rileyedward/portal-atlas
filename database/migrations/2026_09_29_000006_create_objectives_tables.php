<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('giver')->nullable();
            $table->text('rewards')->nullable();
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

        Schema::create('marker_objective', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marker_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['objective_id', 'marker_id']);
        });

        Schema::create('item_objective', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('required');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['objective_id', 'item_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_objective');
        Schema::dropIfExists('marker_objective');
        Schema::dropIfExists('objectives');
    }
};
