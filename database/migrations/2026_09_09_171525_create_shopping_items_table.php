<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Die Einkaufsliste des Haushalts.
     *
     * Bewusst ohne Benutzerbezug: die Liste gehört allen im Haus, nicht dem
     * gerade Angemeldeten.
     */
    public function up(): void
    {
        Schema::create('shopping_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('note', 60)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Die Anzeige sortiert offene Einträge nach vorn, danach nach Alter.
            $table->index(['completed_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
    }
};
