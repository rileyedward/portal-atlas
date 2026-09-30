<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cookieless visit counting. visitor_hash is an HMAC of IP + user agent + date,
        // so it rotates daily and the raw IP / user agent are never stored.
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_hash', 64);
            $table->string('path', 255);
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 10);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['visitor_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
