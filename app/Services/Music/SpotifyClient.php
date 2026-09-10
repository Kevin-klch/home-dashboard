<?php

namespace App\Services\Music;

use App\Models\SpotifyToken;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Liest und steuert die Wiedergabe über die Spotify Web API.
 *
 * Nach einmaliger Anmeldung hält ein Refresh-Token die Verbindung dauerhaft
 * offen; Zugriffstoken laufen nach einer Stunde ab und werden hier
 * selbstständig erneuert.
 */
final class SpotifyClient implements NowPlayingProvider
{
    private const AUTHORIZE_URL = 'https://accounts.spotify.com/authorize';

    private const TOKEN_URL = 'https://accounts.spotify.com/api/token';

    private const API_URL = 'https://api.spotify.com/v1';

    private const CACHE_KEY = 'dashboard.music.playback';

    private const LAST_KEY = 'dashboard.music.last-track';

    private const HISTORY_KEY = 'dashboard.music.history';

    /** Kurz genug, dass ein Titelwechsel schnell auftaucht. */
    private const HISTORY_TTL = 20;

    /** Ohne diesen Scope lässt sich nur zuschauen. */
    public const CONTROL_SCOPE = 'user-modify-playback-state';

    /** Für den Verlauf der zuletzt gespielten Titel. */
    public const HISTORY_SCOPE = 'user-read-recently-played';

    public const SCOPES = 'user-read-playback-state user-read-currently-playing '
        .self::CONTROL_SCOPE.' '.self::HISTORY_SCOPE;

    public function isConfigured(): bool
    {
        return filled(config('services.spotify.client_id'))
            && filled(config('services.spotify.client_secret'));
    }

    public function isConnected(): bool
    {
        return $this->isConfigured() && SpotifyToken::current() !== null;
    }

    /**
     * Darf gesteuert werden?
     *
     * Wurde die Verbindung vor dem Steuerungs-Scope hergestellt, fehlt die
     * Berechtigung im Token – dann hilft nur erneutes Verbinden.
     */
    public function canControl(): bool
    {
        return $this->hasScope(self::CONTROL_SCOPE);
    }

    public function canSeeHistory(): bool
    {
        return $this->hasScope(self::HISTORY_SCOPE);
    }

    private function hasScope(string $scope): bool
    {
        $token = SpotifyToken::current();

        return $token !== null && Str::contains((string) $token->scope, $scope);
    }

    // ------------------------------------------------------------------
    // Anmeldung
    // ------------------------------------------------------------------

    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => config('services.spotify.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.spotify.redirect'),
            'scope' => self::SCOPES,
            'state' => $state,
            // Erzwingt den Zustimmungsdialog, damit ein erneutes Verbinden
            // auch nach einem Widerruf zuverlässig ein Refresh-Token liefert.
            'show_dialog' => 'true',
        ]);
    }

    /** Tauscht den Code aus der Weiterleitung gegen dauerhafte Token. */
    public function exchangeCode(string $code): bool
    {
        $response = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.spotify.redirect'),
        ]);

        if ($response === null || blank($response['refresh_token'] ?? null)) {
            return false;
        }

        SpotifyToken::query()->delete();

        SpotifyToken::query()->create([
            'refresh_token' => $response['refresh_token'],
            'access_token' => $response['access_token'] ?? null,
            'expires_at' => isset($response['expires_in'])
                ? now()->addSeconds((int) $response['expires_in'])
                : null,
            'scope' => $response['scope'] ?? null,
        ]);

        $this->forget();

        return true;
    }

    public function disconnect(): void
    {
        SpotifyToken::query()->delete();
        $this->forget();
        $this->forgetHistory();

        Cache::forget(self::LAST_KEY);
    }

    // ------------------------------------------------------------------
    // Lesen
    // ------------------------------------------------------------------

    public function playback(): Playback
    {
        if (! $this->isConfigured()) {
            return Playback::notConfigured();
        }

        if (SpotifyToken::current() === null) {
            return Playback::disconnected();
        }

        // Kachel und Seite fragen unabhängig voneinander – der kurze Puffer
        // verhindert doppelte Aufrufe im selben Moment.
        $result = Cache::remember(
            self::CACHE_KEY,
            config('dashboard.music.cache_seconds'),
            fn () => $this->fetchPlayer(),
        );

        return match ($result['state'] ?? 'unavailable') {
            'idle' => Playback::idle($this->lastTrack()),
            'playing' => $this->toPlayback($result['body'] ?? []),
            default => Playback::unavailable(),
        };
    }

    /**
     * @return array{state: string, body?: array<string, mixed>}
     */
    private function fetchPlayer(): array
    {
        $request = $this->api();

        if ($request === null) {
            return ['state' => 'unavailable'];
        }

        try {
            // /me/player statt /me/player/currently-playing: nur hier stehen
            // Gerät, Zufallswiedergabe, Wiederholung und Lautstärke mit drin.
            $response = $request->get(self::API_URL.'/me/player', [
                'additional_types' => 'track,episode',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Spotify nicht erreichbar.', ['message' => $e->getMessage()]);

            return ['state' => 'unavailable'];
        }

        // Läuft nichts, antwortet Spotify mit 204 ohne Inhalt. Die
        // Dokumentation nennt für den verwandten Endpunkt nur den 200er mit
        // item: null – in der Praxis kommen beide vor.
        if ($response->status() === 204) {
            return ['state' => 'idle'];
        }

        if ($response->failed()) {
            Log::warning('Spotify antwortete mit einem Fehler.', ['status' => $response->status()]);

            return ['state' => 'unavailable'];
        }

        $body = $response->json();

        if (! is_array($body) || blank($body['item'] ?? null)) {
            return ['state' => 'idle'];
        }

        return ['state' => 'playing', 'body' => $body];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function toPlayback(array $body): Playback
    {
        $track = $this->toNowPlaying($body);

        if ($track === null) {
            return Playback::idle($this->lastTrack());
        }

        // Für die Stille zwischendurch – etwa direkt nach einem Gerätewechsel.
        Cache::put(self::LAST_KEY, $body, now()->addHours(12));

        return Playback::playing($track);
    }

    /** Der zuletzt gesehene Titel, falls einer bekannt ist. */
    private function lastTrack(): ?NowPlaying
    {
        $body = Cache::get(self::LAST_KEY);

        return is_array($body) ? $this->toNowPlaying($body) : null;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function toNowPlaying(array $body): ?NowPlaying
    {
        $item = $body['item'] ?? null;

        if (! is_array($item)) {
            return null;
        }

        $isPodcast = ($item['type'] ?? 'track') === 'episode';

        return new NowPlaying(
            title: (string) ($item['name'] ?? 'Unbekannt'),
            artist: $this->performer($item, $isPodcast),
            album: $isPodcast
                ? ($item['show']['name'] ?? null)
                : ($item['album']['name'] ?? null),
            artworkUrl: $this->artwork($item, $isPodcast),
            isPlaying: (bool) ($body['is_playing'] ?? false),
            progressMs: isset($body['progress_ms']) ? (int) $body['progress_ms'] : null,
            durationMs: isset($item['duration_ms']) ? (int) $item['duration_ms'] : null,
            url: $item['external_urls']['spotify'] ?? null,
            isPodcast: $isPodcast,
            shuffle: (bool) ($body['shuffle_state'] ?? false),
            repeat: (string) ($body['repeat_state'] ?? 'off'),
            deviceName: $body['device']['name'] ?? null,
            volumePercent: isset($body['device']['volume_percent'])
                ? (int) $body['device']['volume_percent']
                : null,
        );
    }

    // ------------------------------------------------------------------
    // Steuern
    // ------------------------------------------------------------------

    public function play(): ControlResult
    {
        return $this->command('put', '/me/player/play');
    }

    public function pause(): ControlResult
    {
        return $this->command('put', '/me/player/pause');
    }

    public function next(): ControlResult
    {
        return $this->command('post', '/me/player/next');
    }

    public function previous(): ControlResult
    {
        return $this->command('post', '/me/player/previous');
    }

    public function seek(int $positionMs): ControlResult
    {
        return $this->command('put', '/me/player/seek', ['position_ms' => max(0, $positionMs)]);
    }

    public function shuffle(bool $on): ControlResult
    {
        return $this->command('put', '/me/player/shuffle', ['state' => $on ? 'true' : 'false']);
    }

    /** "off", "track" oder "context" */
    public function repeat(string $mode): ControlResult
    {
        if (! in_array($mode, ['off', 'track', 'context'], true)) {
            return ControlResult::Failed;
        }

        return $this->command('put', '/me/player/repeat', ['state' => $mode]);
    }

    public function volume(int $percent): ControlResult
    {
        return $this->command('put', '/me/player/volume', [
            'volume_percent' => max(0, min(100, $percent)),
        ]);
    }

    /**
     * Verfügbare Spotify-Connect-Geräte.
     *
     * Geräte tauchen erst auf, wenn sie wach sind und Spotify kennen – ein
     * Echo etwa erst, nachdem dort einmal etwas lief.
     *
     * @return list<AudioDevice>|null  null bei einer Störung
     */
    public function devices(): ?array
    {
        $request = $this->api();

        if ($request === null) {
            return null;
        }

        try {
            $response = $request->get(self::API_URL.'/me/player/devices');
        } catch (ConnectionException $e) {
            Log::warning('Spotify-Geräteliste nicht erreichbar.', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Spotify lieferte keine Geräteliste.', ['status' => $response->status()]);

            return null;
        }

        return array_values(array_filter(array_map(
            fn (array $raw) => AudioDevice::fromApi($raw),
            $response->json('devices') ?? []
        )));
    }

    /**
     * Die zuletzt gespielten Titel.
     *
     * Kommt direkt von Spotify und ist damit vollständig – auch was auf dem
     * Handy unterwegs lief. Der gerade laufende Titel ist nicht enthalten,
     * Podcast-Folgen liefert Spotify hier gar nicht.
     *
     * @return list<PlayedTrack>|null
     */
    public function recentlyPlayed(int $limit = 15): ?array
    {
        if (! $this->canSeeHistory()) {
            return null;
        }

        $limit = max(1, min(50, $limit));

        // Immer das Maximum holen und hier kürzen: ein Schlüssel, der sich
        // nach jedem Befehl gezielt verwerfen lässt.
        $items = Cache::remember(
            self::HISTORY_KEY,
            self::HISTORY_TTL,
            fn () => $this->fetchRecentlyPlayed(50),
        );

        if (! is_array($items)) {
            return null;
        }

        $tracks = array_values(array_filter(array_map(
            fn (array $entry) => $this->toPlayedTrack($entry),
            $items
        )));

        return array_slice($this->collapseRepeats($tracks), 0, $limit);
    }

    /**
     * Denselben Titel mehrfach hintereinander zu einer Zeile zusammenfassen.
     *
     * Jedes Antippen von "weiter" hinterlässt einen eigenen Eintrag – nach
     * ein paar Sprüngen steht derselbe Titel sonst fünfmal untereinander.
     *
     * @param  list<PlayedTrack>  $tracks
     * @return list<PlayedTrack>
     */
    private function collapseRepeats(array $tracks): array
    {
        $kept = [];

        foreach ($tracks as $track) {
            $previous = end($kept) ?: null;

            if ($previous !== null
                && $previous->title === $track->title
                && $previous->artist === $track->artist) {
                continue;
            }

            $kept[] = $track;
        }

        return $kept;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function fetchRecentlyPlayed(int $limit): ?array
    {
        $request = $this->api();

        if ($request === null) {
            return null;
        }

        try {
            $response = $request->get(self::API_URL.'/me/player/recently-played', ['limit' => $limit]);
        } catch (ConnectionException $e) {
            Log::warning('Spotify-Verlauf nicht erreichbar.', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Spotify lieferte keinen Verlauf.', ['status' => $response->status()]);

            return null;
        }

        return $response->json('items') ?? [];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function toPlayedTrack(array $entry): ?PlayedTrack
    {
        $item = $entry['track'] ?? null;

        if (! is_array($item) || blank($item['name'] ?? null)) {
            return null;
        }

        return new PlayedTrack(
            title: (string) $item['name'],
            artist: $this->performer($item, isPodcast: false),
            artworkUrl: $item['album']['images'][1]['url'] ?? $item['album']['images'][0]['url'] ?? null,
            url: $item['external_urls']['spotify'] ?? null,
            playedAt: isset($entry['played_at'])
                ? CarbonImmutable::parse($entry['played_at'])->setTimezone(config('app.timezone'))
                : CarbonImmutable::now(config('app.timezone')),
        );
    }

    /** Wiedergabe auf ein anderes Gerät umlegen. */
    public function transferTo(string $deviceId, bool $keepPlaying = true): ControlResult
    {
        if (blank($deviceId)) {
            return ControlResult::Failed;
        }

        // Die Doku ist eindeutig: mehr als eine ID gibt 400 zurück.
        return $this->command('put', '/me/player', body: [
            'device_ids' => [$deviceId],
            'play' => $keepPlaying,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     */
    private function command(string $method, string $path, array $query = [], ?array $body = null): ControlResult
    {
        if (! $this->canControl()) {
            return ControlResult::NotAllowed;
        }

        $request = $this->api();

        if ($request === null) {
            return ControlResult::Failed;
        }

        $url = self::API_URL.$path.($query === [] ? '' : '?'.http_build_query($query));

        try {
            /** @var Response $response */
            $response = $body === null
                ? $request->{$method}($url)
                : $request->{$method}($url, $body);
        } catch (ConnectionException $e) {
            Log::warning('Spotify-Befehl nicht zustellbar.', ['message' => $e->getMessage()]);

            return ControlResult::Failed;
        }

        // Nach jedem Befehl ist der zwischengespeicherte Zustand überholt –
        // ein Titelsprung landet sofort im Verlauf.
        $this->forget();
        $this->forgetHistory();

        return match (true) {
            $response->successful() => ControlResult::Ok,
            // Ohne aktives Gerät weiß Spotify nicht, wohin mit dem Befehl.
            $response->status() === 404 => ControlResult::NoDevice,
            // 403 kommt unter anderem ohne Premium.
            $response->status() === 403 => ControlResult::NotAllowed,
            default => $this->logAndFail($response),
        };
    }

    private function logAndFail(Response $response): ControlResult
    {
        Log::warning('Spotify lehnte einen Befehl ab.', [
            'status' => $response->status(),
            'reason' => $response->json('error.reason') ?? $response->json('error.message'),
        ]);

        return ControlResult::Failed;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function forgetHistory(): void
    {
        Cache::forget(self::HISTORY_KEY);
    }

    // ------------------------------------------------------------------
    // Hilfsmittel
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $item
     */
    private function performer(array $item, bool $isPodcast): string
    {
        if ($isPodcast) {
            return (string) ($item['show']['publisher'] ?? 'Podcast');
        }

        $names = array_filter(array_map(
            fn ($artist) => $artist['name'] ?? null,
            $item['artists'] ?? []
        ));

        return $names === [] ? 'Unbekannt' : implode(', ', $names);
    }

    /**
     * Das größte angebotene Bild – Spotify sortiert absteigend.
     *
     * @param  array<string, mixed>  $item
     */
    private function artwork(array $item, bool $isPodcast): ?string
    {
        $images = $isPodcast
            ? ($item['images'] ?? $item['show']['images'] ?? [])
            : ($item['album']['images'] ?? []);

        return $images[0]['url'] ?? null;
    }

    /** Vorbereiteter Aufruf mit gültigem Zugriffstoken. */
    private function api(): ?PendingRequest
    {
        $token = $this->accessToken();

        return $token === null ? null : Http::withToken($token)->timeout(5);
    }

    /** Gültiges Zugriffstoken, notfalls frisch geholt. */
    private function accessToken(): ?string
    {
        $token = SpotifyToken::current();

        if ($token === null) {
            return null;
        }

        if ($token->hasUsableAccessToken()) {
            return $token->access_token;
        }

        $response = $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
        ]);

        if ($response === null || blank($response['access_token'] ?? null)) {
            return null;
        }

        $token->update([
            'access_token' => $response['access_token'],
            'expires_at' => isset($response['expires_in'])
                ? now()->addSeconds((int) $response['expires_in'])
                : null,
            // Spotify tauscht das Refresh-Token gelegentlich aus.
            'refresh_token' => $response['refresh_token'] ?? $token->refresh_token,
            'scope' => $response['scope'] ?? $token->scope,
        ]);

        return $response['access_token'];
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, mixed>|null
     */
    private function requestToken(array $payload): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->withBasicAuth(
                    (string) config('services.spotify.client_id'),
                    (string) config('services.spotify.client_secret'),
                )
                ->post(self::TOKEN_URL, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Spotify-Anmeldung nicht erreichbar.', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Spotify lehnte die Token-Anfrage ab.', [
                'status' => $response->status(),
                'error' => $response->json('error_description') ?? $response->json('error'),
            ]);

            return null;
        }

        $body = $response->json();

        return is_array($body) ? $body : null;
    }
}
