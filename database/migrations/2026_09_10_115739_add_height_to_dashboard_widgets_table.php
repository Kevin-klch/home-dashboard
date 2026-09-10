<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Höhe einer Kachel in Rasterzeilen.
     *
     * Eine Zeile ist die Grundhöhe des Rasters; zwei Zeilen ergeben eine
     * Kachel, die über zwei davon reicht.
     */
    public function up(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->unsignedTinyInteger('height')->default(1)->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->dropColumn('height');
        });
    }
};
