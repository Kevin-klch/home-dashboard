<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anordnung der Kacheln auf dem Dashboard.
     *
     * Nur die Anordnung – welche Kacheln es überhaupt gibt, steht in der
     * WidgetRegistry. Ein Eintrag hier heißt: diese Kachel liegt an dieser
     * Stelle in dieser Breite.
     */
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget', 40)->unique();
            $table->unsignedSmallInteger('position');
            // Breite in Spalten des Sechser-Rasters.
            $table->unsignedTinyInteger('width')->default(2);
            $table->timestamps();

            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
