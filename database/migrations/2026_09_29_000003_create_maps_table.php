<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            // Base layer image (optional). Width/height define the coordinate aspect ratio.
            $table->string('image_path')->nullable();
            $table->string('image_attribution')->nullable();
            $table->unsignedInteger('width')->default(1000);
            $table->unsignedInteger('height')->default(1000);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('game_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_url')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
