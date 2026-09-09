<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ablage für die Spotify-Anmeldung.
     *
     * Das Refresh-Token läuft nicht ab und ersetzt damit ein erneutes
     * Anmelden. Es gehört deshalb dauerhaft gespeichert – und verschlüsselt,
     * denn wer es hat, kann auf das Spotify-Konto zugreifen.
     */
    public function up(): void
    {
        Schema::create('spotify_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('refresh_token');
            $table->text('access_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('scope')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotify_tokens');
    }
};
