<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PIN für die Anmeldung am Wandtablet.
     *
     * Wird wie ein Passwort gehasht – im Klartext steht sie nirgends. Nullable,
     * weil sie erst im Profil eingerichtet wird und die Anmeldung per E-Mail
     * weiterhin funktionieren muss.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
