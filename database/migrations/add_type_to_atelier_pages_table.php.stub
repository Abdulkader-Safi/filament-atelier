<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atelier_pages', function (Blueprint $table) {
            // Which registered page type this row is: 'service', 'product',
            // or 'page' for an ordinary one. Indexed because every list in
            // the panel and every listing block filters on it.
            //
            // Defaulted rather than nullable so there is one way to ask the
            // question. An install upgrading into this has every existing row
            // become a plain page, which is what it already was.
            $table->string('type')->default('page')->index()->after('title');

            // The values for whatever fields the type declared. Keys the type
            // named translatable hold a per-locale map, the same shape block
            // attributes use; everything else holds one value.
            $table->json('data')->nullable()->after('schema');
        });
    }

    public function down(): void
    {
        Schema::table('atelier_pages', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'data']);
        });
    }
};
