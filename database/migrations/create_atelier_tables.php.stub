<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atelier_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft')->index();
            $table->string('layout')->nullable();

            // Editing writes draft_content. Publishing copies it to
            // published_content. The public route reads published only.
            $table->json('draft_content')->nullable();
            $table->json('published_content')->nullable();

            // Per-locale meta: title, description, og image, canonical.
            $table->json('seo')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Slugs get their own table because a JSON map cannot carry a unique
        // index, and two pages sharing a slug in one locale is a real bug.
        Schema::create('atelier_page_slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('atelier_pages')->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('slug');
            $table->timestamps();

            $table->unique(['locale', 'slug']);
            $table->index(['page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_page_slugs');
        Schema::dropIfExists('atelier_pages');
    }
};
