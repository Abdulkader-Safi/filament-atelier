<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the public forms write and the panel reads back. Example-app only:
 * where a submission should land is still an open question in the PRD, and
 * the package deliberately has no opinion yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();

            // 'service' or 'product'. One table rather than two, because the
            // two differ by three columns and every screen wants them
            // side by side.
            $table->string('kind')->index();

            // The page requested. Nullable and null-on-delete, with the title
            // copied, so a deleted service leaves a readable record instead
            // of an orphan row.
            $table->foreignId('page_id')->nullable()->constrained('atelier_pages')->nullOnDelete();
            $table->string('page_title');

            // The tier for a service, the variation for a product.
            $table->string('option')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total', 10, 2)->nullable();

            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();

            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
