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

        Http::assertSentCount(1);
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
}
