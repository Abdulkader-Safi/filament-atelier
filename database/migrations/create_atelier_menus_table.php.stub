<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per location. `items` is the whole tree for that location:
        // a flat top level, each item optionally carrying one level of
        // `children`. See src/Models/Menu.php for why this is a JSON column
        // rather than a relational nested-set or adjacency-list table.
        Schema::create('atelier_menus', function (Blueprint $table) {
            $table->id();
            $table->string('location')->unique();
            $table->json('items')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_menus');
    }
};
