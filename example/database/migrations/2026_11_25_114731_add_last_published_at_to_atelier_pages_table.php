<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atelier_pages', function (Blueprint $table) {
            // When the live version last changed. published_at becomes the
            // first publish and stays put; this one moves on every publish
            // and feeds both the sitemap's lastmod and dateModified, so the
            // two can never disagree. A draft save touches neither.
            $table->timestamp('last_published_at')->nullable()->after('published_at');
        });

        // Until now publish() overwrote published_at every time, so it
        // already holds the last publish. The original first date is gone.
        DB::table('atelier_pages')->update(['last_published_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        Schema::table('atelier_pages', function (Blueprint $table) {
            $table->dropColumn('last_published_at');
        });
    }
};
