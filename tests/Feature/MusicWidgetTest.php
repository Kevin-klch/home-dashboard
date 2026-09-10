<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Music;
use App\Models\SpotifyToken;
use App\Services\Music\SpotifyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Livewire\Livewire;
use Tests\TestCase;

class MusicWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.spotify.client_id' => 'test-id',
            'services.spotify.client_secret' => 'test-secret',
        ]);
    }

    private function connect(?string $scope = null): SpotifyToken
    {
        return SpotifyToken::query()->create([
            'refresh_token' => 'refresh-123',
            'access_token' => 'access-123',
            'expires_at' => now()->addHour(),
            'scope' => $scope ?? SpotifyClient::SCOPES,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trackPayload(bool $playing = true): array
    {
        return [
            'is_playing' => $playing,
            'progress_ms' => 83_000,
            'item' => [
                'type' => 'track',
                'name' => 'Blaue Stunde',
                'duration_ms' => 225_000,
                'artists' => [['name' => 'Anna Beispiel'], ['name' => 'Ben Muster']],
                'album' => [
                    'name' => 'Nachtfahrt',
                    'images' => [['url' => 'https://i.scdn.co/image/gross']],
                ],
                'external_urls' => ['spotify' => 'https://open.spotify.com/track/abc'],
            ],
        ];
    }

    public function test_it_asks_for_setup_without_credentials(): void
    {
        config(['services.spotify.client_id' => null]);

        Livewire::test(Music::class)->assertSee('Spotify nicht eingerichtet');

        Http::assertNothingSent();
    }

    public function test_it_asks_to_connect_when_no_account_is_linked(): void
    {
        Livewire::test(Music::class)->assertSee('Noch nicht verbunden');

        Http::assertNothingSent();
    }

    public function test_it_shows_the_running_track(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class)
            ->assertSeeHtml('data-state="laeuft"')
            ->assertSee('Blaue Stunde')
            ->assertSee('Anna Beispiel, Ben Muster')
            ->assertSee('1:23')   // Fortschritt
            ->assertSee('3:45');  // Länge
    }

    public function test_it_marks_a_paused_track(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload(playing: false))]);

        Livewire::test(Music::class)->assertSee('Pausiert');
    }

    public function test_it_reports_silence_on_a_204(): void
    {
        // Spotify antwortet mit 204 ohne Inhalt, wenn nichts läuft – die
        // Dokumentation nennt nur den 200er mit item: null.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response('', 204)]);

        Livewire::test(Music::class)
            ->assertSeeHtml('data-state="still"')
            ->assertSee('Gerade läuft nichts');
    }

    public function test_it_reports_silence_when_the_item_is_null(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response(['is_playing' => false, 'item' => null])]);

        Livewire::test(Music::class)->assertSee('Gerade läuft nichts');
    }

    public function test_it_separates_a_fault_from_silence(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response('', 500)]);

        Livewire::test(Music::class)
            ->assertSee('Spotify nicht erreichbar')
            ->assertDontSee('Gerade läuft nichts');
    }

    public function test_it_handles_a_podcast_episode(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response([
            'is_playing' => true,
            'progress_ms' => 5_000,
            'item' => [
                'type' => 'episode',
                'name' => 'Folge 42',
                'duration_ms' => 1_800_000,
                'images' => [['url' => 'https://i.scdn.co/image/podcast']],
                'show' => ['name' => 'Küchenradio', 'publisher' => 'Studio Nord'],
            ],
        ])]);

        Livewire::test(Music::class)
            ->assertSee('Folge 42')
            ->assertSee('Studio Nord');
    }

    public function test_it_renews_an_expired_access_token(): void
    {
        $token = $this->connect();
        $token->update(['expires_at' => now()->subMinutes(5)]);

        Http::fake([
            'accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-neu',
                'expires_in' => 3600,
            ]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        Livewire::test(Music::class)->assertSee('Blaue Stunde');

        $this->assertSame('access-neu', $token->fresh()->access_token);
        $this->assertTrue($token->fresh()->expires_at->isFuture());
    }

    public function test_it_keeps_a_rotated_refresh_token(): void
    {
        $token = $this->connect();
        $token->update(['expires_at' => now()->subMinutes(5)]);

        Http::fake([
            'accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-neu',
                'expires_in' => 3600,
                'refresh_token' => 'refresh-neu',
            ]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        Livewire::test(Music::class);

        $this->assertSame('refresh-neu', $token->fresh()->refresh_token);
    }

    public function test_tile_and_page_share_one_request(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['variant' => 'tile']);
        Livewire::test(Music::class, ['variant' => 'page']);

        // Der Verlauf ist ein eigener Endpunkt; gemeint ist die Wiedergabe.
        $playbackCalls = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), '/me/player?'))
            ->count();

        $this->assertSame(1, $playbackCalls);
    }

    public function test_the_page_variant_shows_more_detail(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->assertSee('Nachtfahrt')          // Album, nur auf der Seite
            ->assertSee('In Spotify öffnen');
    }

    public function test_an_unknown_variant_falls_back_to_the_tile(): void
    {
        // Der Wert landet im Ansichtsnamen und darf nichts Fremdes durchlassen.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['variant' => '../../etc/passwd'])
            ->assertOk()
            ->assertSee('Blaue Stunde');
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        Livewire::test(Music::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }

    // ------------------------------------------------------------------
    // Steuerung
    // ------------------------------------------------------------------

    public function test_the_play_button_pauses_a_running_track(): void
    {
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/pause*' => Http::response('', 204),
            'api.spotify.com/*' => Http::response($this->trackPayload(playing: true)),
        ]);

        Livewire::test(Music::class)->call('togglePlay');

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/me/player/pause'));
    }

    public function test_the_play_button_resumes_a_paused_track(): void
    {
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/play*' => Http::response('', 204),
            'api.spotify.com/*' => Http::response($this->trackPayload(playing: false)),
        ]);

        Livewire::test(Music::class)->call('togglePlay');

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/me/player/play'));
    }

    public function test_it_skips_forward_and_back(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class)->call('next')->call('previous');

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/me/player/next'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/me/player/previous'));
    }

    public function test_it_toggles_shuffle_to_the_opposite_state(): void
    {
        $this->connect();
        $payload = $this->trackPayload();
        $payload['shuffle_state'] = false;
        Http::fake(['api.spotify.com/*' => Http::response($payload)]);

        Livewire::test(Music::class)->call('toggleShuffle');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/me/player/shuffle')
            && str_contains($request->url(), 'state=true'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function repeatModes(): array
    {
        return [
            'aus wird alles' => ['off', 'context'],
            'alles wird einer' => ['context', 'track'],
            'einer wird aus' => ['track', 'off'],
        ];
    }

    #[DataProvider('repeatModes')]
    public function test_repeat_cycles_through_the_three_modes(string $current, string $expected): void
    {
        $this->connect();

        $payload = $this->trackPayload();
        $payload['repeat_state'] = $current;

        Http::fake(['api.spotify.com/*' => Http::response($payload)]);

        Livewire::test(Music::class)->call('cycleRepeat');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/me/player/repeat')
            && str_contains($request->url(), "state={$expected}"));
    }

    public function test_it_clamps_the_volume(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class)->call('setVolume', 150);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'volume_percent=100'));
    }

    public function test_seeking_converts_percent_into_milliseconds(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        // Die Länge im Testtitel sind 225000 ms, die Hälfte also 112500.
        Livewire::test(Music::class)->call('seekToPercent', 50);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'position_ms=112500'));
    }

    public function test_it_explains_a_missing_device(): void
    {
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/next*' => Http::response(['error' => ['status' => 404]], 404),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        Livewire::test(Music::class)
            ->call('next')
            ->assertSee('Kein aktives Gerät');
    }

    public function test_it_refuses_to_control_without_the_scope(): void
    {
        // Verbindung von vor der Steuerungs-Erweiterung.
        $this->connect(scope: 'user-read-currently-playing');
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class)
            ->call('next')
            ->assertSee('fehlt die Berechtigung');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/me/player/next'));
    }

    public function test_it_offers_to_renew_the_connection_without_the_scope(): void
    {
        $this->connect(scope: 'user-read-currently-playing');
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->assertSee('Verbindung erneuern')
            ->assertSeeHtml('disabled');
    }

    public function test_a_command_refreshes_the_cached_state(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        // Erst rendern (füllt den Cache), dann ein Befehl, dann erneut rendern.
        Livewire::test(Music::class)->call('next');

        // Ohne Cache-Verwerfen bliebe es bei einem einzigen Lesezugriff.
        Http::assertSentCount(3);
    }

    // ------------------------------------------------------------------
    // Ausgabegerät
    // ------------------------------------------------------------------

    /**
     * @param  list<array<string, mixed>>  $devices
     */
    private function fakeWithDevices(array $devices): void
    {
        Http::fake([
            'api.spotify.com/v1/me/player/devices*' => Http::response(['devices' => $devices]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);
    }

    public function test_it_lists_the_available_devices(): void
    {
        $this->connect();
        $this->fakeWithDevices([
            ['id' => 'desk-1', 'name' => 'Arbeitsplatz', 'type' => 'Computer', 'is_active' => true],
            ['id' => 'echo-1', 'name' => 'Echo Küche', 'type' => 'Speaker', 'is_active' => false],
        ]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('toggleDevices')
            ->assertSet('showDevices', true)
            ->assertSeeHtml('data-devices')
            ->assertSeeHtml('data-device="Arbeitsplatz"')
            ->assertSeeHtml('data-device="Echo Küche"');
    }

    public function test_it_does_not_load_devices_before_the_picker_is_opened(): void
    {
        // Sonst zöge jedes Pollen die Geräteliste mit.
        $this->connect();
        $this->fakeWithDevices([
            ['id' => 'desk-1', 'name' => 'Arbeitsplatz', 'type' => 'Computer', 'is_active' => true],
        ]);

        Livewire::test(Music::class, ['variant' => 'page']);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/me/player/devices'));
    }

    public function test_it_transfers_playback_to_the_chosen_device(): void
    {
        $this->connect();
        $this->fakeWithDevices([
            ['id' => 'echo-1', 'name' => 'Echo Küche', 'type' => 'Speaker', 'is_active' => false],
        ]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('toggleDevices')
            ->call('transferTo', 'echo-1')
            ->assertSet('showDevices', false);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.spotify.com/v1/me/player'
                && $request['device_ids'] === ['echo-1']
                && $request['play'] === true;
        });
    }

    public function test_it_marks_the_active_device_and_blocks_restricted_ones(): void
    {
        $this->connect();
        $this->fakeWithDevices([
            ['id' => 'desk-1', 'name' => 'Arbeitsplatz', 'type' => 'Computer', 'is_active' => true],
            ['id' => 'tv-1', 'name' => 'Fernseher', 'type' => 'TV', 'is_active' => false, 'is_restricted' => true],
        ]);

        $html = Livewire::test(Music::class, ['variant' => 'page'])->call('toggleDevices')->html();

        $this->assertStringContainsString('gesperrt', $html);

        // Attributreihenfolge ist nicht garantiert, deshalb den ganzen Knopf prüfen.
        preg_match('/<button[^>]*data-device="Fernseher"[^>]*>/s', $html, $button);

        $this->assertNotEmpty($button, 'Der Knopf für das gesperrte Gerät fehlt.');
        $this->assertStringContainsString('disabled', $button[0]);
    }

    public function test_it_explains_an_empty_device_list(): void
    {
        $this->connect();
        $this->fakeWithDevices([]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('toggleDevices')
            ->assertSee('Kein Gerät gefunden');
    }

    public function test_devices_without_an_id_are_skipped(): void
    {
        // Spotify liefert für manche Geräte keine ID – ohne sie ließe sich
        // ohnehin nicht umschalten.
        $this->connect();
        $this->fakeWithDevices([
            ['id' => null, 'name' => 'Namenloses Gerät', 'type' => 'Speaker'],
            ['id' => 'echo-1', 'name' => 'Echo Küche', 'type' => 'Speaker'],
        ]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('toggleDevices')
            ->assertDontSee('Namenloses Gerät')
            ->assertSee('Echo Küche');
    }

    public function test_a_failed_transfer_keeps_the_picker_open(): void
    {
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/devices*' => Http::response(['devices' => [
                ['id' => 'echo-1', 'name' => 'Echo Küche', 'type' => 'Speaker'],
            ]]),
            'api.spotify.com/v1/me/player' => Http::response(['error' => ['status' => 404]], 404),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('toggleDevices')
            ->call('transferTo', 'echo-1')
            ->assertSet('showDevices', true)
            ->assertSee('Kein aktives Gerät');
    }

    public function test_it_refuses_a_transfer_without_the_control_scope(): void
    {
        $this->connect(scope: 'user-read-playback-state');
        $this->fakeWithDevices([['id' => 'echo-1', 'name' => 'Echo Küche', 'type' => 'Speaker']]);

        Livewire::test(Music::class, ['variant' => 'page'])
            ->call('transferTo', 'echo-1')
            ->assertSee('fehlt die Berechtigung');

        Http::assertNotSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'https://api.spotify.com/v1/me/player');
    }

    // ------------------------------------------------------------------
    // Darstellung bei viel Platz
    // ------------------------------------------------------------------

    public function test_a_small_tile_stays_compact(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['cols' => 2, 'rows' => 1])
            ->assertSeeHtml('data-stage="kompakt"');
    }

    public function test_a_wide_but_flat_tile_stays_compact(): void
    {
        // Genau der Fall, in dem ein großes Cover die Kachel sprengen würde.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['cols' => 4, 'rows' => 1])
            ->assertSeeHtml('data-stage="kompakt"');
    }

    public function test_a_roomy_tile_gets_the_big_stage(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['cols' => 4, 'rows' => 2])
            ->assertSeeHtml('data-stage="gross"');
    }

    public function test_a_tall_but_narrow_tile_stays_compact(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['cols' => 2, 'rows' => 3])
            ->assertSeeHtml('data-stage="kompakt"');
    }

    public function test_the_cover_never_inflates_the_tile(): void
    {
        // Absolut positioniert: das Cover fuellt den Rest, traegt aber selbst
        // nichts zur Hoehe bei. Sonst waechst die Rasterzeile mit dem Bild.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        $html = Livewire::test(Music::class, ['cols' => 4, 'rows' => 2])->html();

        $this->assertMatchesRegularExpression('/<img[^>]*data-cover[^>]*class="[^"]*absolute/s', $html);
    }

    public function test_the_grid_size_cannot_be_pushed_beyond_the_grid(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['cols' => 99, 'rows' => 99])
            ->assertSet('cols', 6)
            ->assertSet('rows', 4);
    }

    public function test_the_artwork_also_serves_as_a_backdrop(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        $html = Livewire::test(Music::class)->html();

        // Einmal als Cover, einmal weichgezeichnet als Hintergrund.
        $this->assertSame(2, substr_count($html, 'https://i.scdn.co/image/gross'));
        $this->assertStringContainsString('blur-3xl', $html);
    }

    public function test_there_is_no_backdrop_without_a_track(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response('', 204)]);

        Livewire::test(Music::class)->assertDontSeeHtml('blur-3xl');
    }

    // ------------------------------------------------------------------
    // Letzter Titel und Verlauf
    // ------------------------------------------------------------------

    public function test_the_last_track_stays_visible_during_silence(): void
    {
        // Genau der Fall nach einem Gerätewechsel: Spotify meldet kurz Stille.
        $this->connect();

        $silent = false;
        Http::fake(function () use (&$silent) {
            return $silent
                ? Http::response('', 204)
                : Http::response($this->trackPayload());
        });

        Livewire::test(Music::class)->assertSee('Blaue Stunde');

        $silent = true;
        // Im Test vergeht keine Zeit, der Wiedergabe-Cache muss also weg.
        app(SpotifyClient::class)->forget();

        Livewire::test(Music::class)
            ->assertSeeHtml('data-state="pause"')
            ->assertSee('Blaue Stunde')
            ->assertSee('Zuletzt gespielt')
            ->assertDontSee('Gerade läuft nichts');
    }

    public function test_without_any_history_it_still_says_nothing_is_playing(): void
    {
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response('', 204)]);

        Livewire::test(Music::class)
            ->assertSeeHtml('data-state="still"')
            ->assertSee('Gerade läuft nichts');
    }

    public function test_the_page_lists_the_recently_played_tracks(): void
    {
        $this->connect();

        Http::fake([
            'api.spotify.com/v1/me/player/recently-played*' => Http::response([
                'items' => [
                    [
                        'played_at' => now()->subMinutes(4)->toIso8601String(),
                        'track' => [
                            'name' => 'Nachtfahrt',
                            'artists' => [['name' => 'Anna Beispiel']],
                            'album' => ['images' => [['url' => 'https://i.scdn.co/image/a']]],
                            'external_urls' => ['spotify' => 'https://open.spotify.com/track/a'],
                        ],
                    ],
                    [
                        'played_at' => now()->subHours(3)->toIso8601String(),
                        'track' => [
                            'name' => 'Morgenlicht',
                            'artists' => [['name' => 'Ben Muster']],
                            'album' => ['images' => [['url' => 'https://i.scdn.co/image/b']]],
                        ],
                    ],
                ],
            ]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        $component = Livewire::test(Music::class, ['variant' => 'page']);

        $component->assertSeeHtml('data-history')
            ->assertSee('Zuletzt gehört')
            ->assertSee('Nachtfahrt')
            ->assertSee('Morgenlicht')
            ->assertSee('vor 4 Min.');

        $this->assertSame(2, substr_count($component->html(), 'data-played'));
    }

    public function test_the_tile_does_not_fetch_the_history(): void
    {
        // Auf der Kachel wäre kein Platz dafür – also gar nicht erst holen.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        Livewire::test(Music::class, ['variant' => 'tile']);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'recently-played'));
    }

    public function test_the_history_needs_its_own_permission(): void
    {
        $this->connect(scope: SpotifyClient::SCOPES);
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        // Verbindung von vor der Verlaufs-Erweiterung.
        $this->connect(scope: 'user-read-playback-state user-modify-playback-state');

        Livewire::test(Music::class, ['variant' => 'page'])
            ->assertSee('Für den Verlauf fehlt die Berechtigung');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'recently-played'));
    }

    public function test_the_history_scope_is_requested_on_connect(): void
    {
        $this->assertStringContainsString('user-read-recently-played', SpotifyClient::SCOPES);
    }

    public function test_the_music_page_fills_the_screen_without_scrolling(): void
    {
        // Auf dem Wandtablet soll nichts gescrollt werden – nur der Verlauf.
        $this->connect();
        Http::fake(['api.spotify.com/*' => Http::response($this->trackPayload())]);

        $html = $this->actingAs(\App\Models\User::factory()->create())
            ->get(route('music'))
            ->getContent();

        $this->assertStringContainsString('overflow-hidden', $html);
        $this->assertStringNotContainsString('min-h-full', $html);
    }

    public function test_the_history_column_scrolls_on_its_own(): void
    {
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/recently-played*' => Http::response(['items' => []]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        $html = Livewire::test(Music::class, ['variant' => 'page'])->html();

        $this->assertMatchesRegularExpression('/<aside[^>]*data-history/s', $html);
    }

    public function test_it_always_fetches_the_maximum_and_shortens_locally(): void
    {
        // Ein Abruf, ein Cache-Schlüssel – so lässt er sich nach einem Befehl
        // gezielt verwerfen, ohne die Länge zu kennen.
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/recently-played*' => Http::response(['items' => []]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        Livewire::test(Music::class, ['variant' => 'page']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'recently-played')
            && (int) $request['limit'] === 50);
    }

    public function test_it_returns_at_most_the_requested_number(): void
    {
        $this->connect();

        $items = [];
        for ($i = 0; $i < 40; $i++) {
            $items[] = [
                'played_at' => now()->subMinutes($i)->toIso8601String(),
                'track' => [
                    'name' => "Titel {$i}",
                    'artists' => [['name' => 'Anna Beispiel']],
                    'album' => ['images' => []],
                ],
            ];
        }

        Http::fake(['api.spotify.com/*' => Http::response(['items' => $items])]);

        $this->assertCount(5, app(SpotifyClient::class)->recentlyPlayed(5));
        $this->assertCount(30, app(SpotifyClient::class)->recentlyPlayed(30));
    }

    public function test_the_same_track_in_a_row_becomes_one_entry(): void
    {
        // Jedes Antippen von "weiter" hinterlaesst einen eigenen Eintrag.
        $this->connect();

        $repeat = fn (string $name, int $minutes) => [
            'played_at' => now()->subMinutes($minutes)->toIso8601String(),
            'track' => [
                'name' => $name,
                'artists' => [['name' => 'Wir sind Helden']],
                'album' => ['images' => []],
            ],
        ];

        Http::fake(['api.spotify.com/*' => Http::response(['items' => [
            $repeat('Von hier an blind', 1),
            $repeat('Von hier an blind', 2),
            $repeat('Von hier an blind', 3),
            $repeat('Nur ein Wort', 4),
            $repeat('Von hier an blind', 5),
        ]])]);

        $history = app(SpotifyClient::class)->recentlyPlayed(30);

        // Dreimal hintereinander wird eins; das spaetere Vorkommen bleibt.
        $this->assertCount(3, $history);
        $this->assertSame('Von hier an blind', $history[0]->title);
        $this->assertSame('Nur ein Wort', $history[1]->title);
        $this->assertSame('Von hier an blind', $history[2]->title);
    }

    public function test_a_command_refreshes_the_history(): void
    {
        // Sonst dauert es bis zu einer Minute, bis ein Sprung sichtbar wird.
        $this->connect();
        Http::fake([
            'api.spotify.com/v1/me/player/recently-played*' => Http::response(['items' => []]),
            'api.spotify.com/*' => Http::response($this->trackPayload()),
        ]);

        $spotify = app(SpotifyClient::class);
        $spotify->recentlyPlayed(30);
        $spotify->next();
        $spotify->recentlyPlayed(30);

        $calls = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'recently-played'))
            ->count();

        $this->assertSame(2, $calls);
    }
}
