<?php

namespace App\Http\Controllers;

use App\Services\Music\SpotifyClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Einmalige Anmeldung bei Spotify.
 *
 * Danach hält ein Refresh-Token die Verbindung offen, hier passiert nichts
 * mehr – bis auf das Trennen.
 */
class SpotifyController extends Controller
{
    private const STATE_KEY = 'spotify.state';

    public function connect(Request $request, SpotifyClient $spotify): RedirectResponse
    {
        if (! $spotify->isConfigured()) {
            return redirect()->route('music')
                ->with('spotify-error', 'Es fehlen die Zugangsdaten in der .env-Datei.');
        }

        // Schutz davor, dass jemand eine fremde Rückleitung unterschiebt.
        $state = Str::random(40);
        $request->session()->put(self::STATE_KEY, $state);

        return redirect()->away($spotify->authorizationUrl($state));
    }

    public function callback(Request $request, SpotifyClient $spotify): RedirectResponse
    {
        $expected = $request->session()->pull(self::STATE_KEY);

        if ($request->query('error')) {
            return redirect()->route('music')
                ->with('spotify-error', 'Spotify hat die Verbindung abgelehnt: '.$request->query('error'));
        }

        if (blank($expected) || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('music')
                ->with('spotify-error', 'Die Rückleitung passte nicht zur Anfrage. Bitte noch einmal versuchen.');
        }

        $code = (string) $request->query('code');

        if (blank($code) || ! $spotify->exchangeCode($code)) {
            return redirect()->route('music')
                ->with('spotify-error', 'Der Zugriff konnte nicht abgeschlossen werden. Stimmen Client-ID und Weiterleitungs-URL?');
        }

        return redirect()->route('music')->with('spotify-status', 'Spotify ist verbunden.');
    }

    public function disconnect(SpotifyClient $spotify): RedirectResponse
    {
        $spotify->disconnect();

        return redirect()->route('music')->with('spotify-status', 'Die Verbindung wurde getrennt.');
    }
}
