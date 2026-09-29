<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports become general player feedback: the subject is optional, and a
 * submission can carry the page it came from, a reply email for guests and a
 * suggested map position.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('reportable_type')->nullable()->change();
            $table->unsignedBigInteger('reportable_id')->nullable()->change();
            $table->foreignId('map_id')->nullable()->after('reportable_id')->constrained()->nullOnDelete();
            $table->string('context')->nullable()->after('message');
            $table->string('page_url', 500)->nullable()->after('context');
            $table->string('email')->nullable()->after('page_url');
            $table->decimal('suggested_x', 7, 4)->nullable()->after('email');
            $table->decimal('suggested_y', 7, 4)->nullable()->after('suggested_x');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('map_id');
            $table->dropColumn(['context', 'page_url', 'email', 'suggested_x', 'suggested_y']);
        });
    }
};
