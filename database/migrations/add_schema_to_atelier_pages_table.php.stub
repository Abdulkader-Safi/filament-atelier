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
            // What this page is, in schema.org terms, plus whatever that type
            // needs. Page-level rather than per locale: a page that is a
            // Service in English is a Service in Arabic. It is one fact about
            // the page, the same way the layout is.
            $table->json('schema')->nullable()->after('seo');
        });
    }

    public function down(): void
    {
        Schema::table('atelier_pages', function (Blueprint $table) {
            $table->dropColumn('schema');
        });
    }
};
