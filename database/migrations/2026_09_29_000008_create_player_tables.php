<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->decimal('x', 7, 4);
            $table->decimal('y', 7, 4);
            $table->string('title', 120);
            $table->text('body')->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_shared')->default(false);
            $table->string('share_token', 32)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'map_id']);
        });

        Schema::create('marker_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marker_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('discovered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'marker_id']);
        });

        Schema::create('item_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('intent', 20)->nullable();
            $table->unsignedInteger('quantity_needed')->default(0);
            $table->unsignedInteger('quantity_owned')->default(0);
            $table->boolean('is_favorite')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'item_id']);
        });

        Schema::create('raid_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            // Ordered list of {x, y, marker_id?, label?}.
            $table->jsonb('points');
            $table->boolean('is_public')->default(false);
            $table->string('share_token', 32)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raid_routes');
        Schema::dropIfExists('item_user');
        Schema::dropIfExists('marker_user');
        Schema::dropIfExists('map_notes');
    }
};
