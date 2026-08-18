<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row, holding the facts about the site itself: who publishes it,
        // its logo, its social profiles, its address.
        //
        // This is client-owned data that changes without a deploy, which is
        // why it is a table rather than config. Tokens, locales and layouts
        // are a developer's decisions and stay in config; a phone number is
        // not.
        //
        // A JSON column rather than typed columns, because the shape follows
        // schema.org and grows a field at a time. Nothing queries into it.
        Schema::create('atelier_settings', function (Blueprint $table) {
            $table->id();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_settings');
    }
};
