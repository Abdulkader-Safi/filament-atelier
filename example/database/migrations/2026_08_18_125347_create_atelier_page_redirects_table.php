<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renaming a slug on a page that is already ranking used to 404 every
        // inbound link to it, silently and permanently. A row here is written
        // whenever a slug changes, and the public route consults it before it
        // gives up.
        //
        // The target is the page, not a slug, so a page renamed twice leaves
        // two rows both pointing at wherever it lives now. No chains to follow
        // and none to clean up.
        Schema::create('atelier_page_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 12);
            $table->string('from_slug');
            $table->foreignId('page_id')->constrained('atelier_pages')->cascadeOnDelete();
            $table->unsignedSmallInteger('status')->default(301);
            $table->timestamps();

            // One destination per old URL, and the lookup the router does.
            $table->unique(['locale', 'from_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_page_redirects');
    }
};
