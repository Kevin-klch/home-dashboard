<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Die gespeicherte Spotify-Anmeldung. Es gibt immer höchstens eine.
 */
class SpotifyToken extends Model
{
    protected $fillable = [
        'refresh_token',
        'access_token',
        'expires_at',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            // Verschlüsselt in der Datenbank: wer das Refresh-Token hat,
            // kommt an das Spotify-Konto.
            'refresh_token' => 'encrypted',
            'access_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public function hasUsableAccessToken(): bool
    {
        return filled($this->access_token)
            && $this->expires_at !== null
            // Eine Minute Puffer, damit ein Token nicht mitten im Aufruf abläuft.
            && $this->expires_at->isAfter(now()->addMinute());
    }
}
