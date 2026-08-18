<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A snapshot per publish. This solves a different problem from the two
        // content columns: not "don't publish yet" but "put back what it was
        // last week". Both are wanted and they are separate mechanisms.
        Schema::create('atelier_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('atelier_pages')->cascadeOnDelete();
            $table->json('content')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('label')->nullable();
            $table->timestamp('created_at')->nullable();

            // Pruning and listing both read newest first for one page.
            $table->index(['page_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_page_revisions');
    }
};
