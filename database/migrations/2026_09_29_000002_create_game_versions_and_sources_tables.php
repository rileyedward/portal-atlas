<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 50)->unique();
            $table->string('name')->nullable();
            $table->date('released_at')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->text('notes')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        // Provenance for every public data point. Reliability (0-100) feeds the confidence model.
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 30)->index();
            $table->string('url')->nullable();
            $table->unsignedTinyInteger('reliability')->default(50);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
        Schema::dropIfExists('game_versions');
    }
};
