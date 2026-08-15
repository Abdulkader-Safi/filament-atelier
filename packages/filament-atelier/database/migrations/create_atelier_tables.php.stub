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

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_pages');
    }
};
