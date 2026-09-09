<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notizzettel des Haushalts.
     *
     * Wie die Einkaufsliste bewusst ohne Benutzerbezug – was am Kühlschrank
     * hängt, hängt für alle da.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('body', 500);
            // Angeheftete Notizen stehen oben und werden nicht weggeräumt.
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();

            $table->index(['pinned_at', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
