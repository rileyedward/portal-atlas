<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('reportable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('open')->index();
            // Salted hash only, used for rate-limiting/abuse detection. Never the raw IP.
            $table->string('reporter_fingerprint', 64)->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_version_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['verifiable_type', 'verifiable_id', 'user_id', 'game_version_id'], 'verifications_unique_per_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('reports');
    }
};
