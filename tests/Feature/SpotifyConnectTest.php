<?php

namespace Tests\Feature;

use App\Models\SpotifyToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.spotify.client_id' => 'test-id',
            'services.spotify.client_secret' => 'test-secret',
            'services.spotify.redirect' => 'http://127.0.0.1:8000/spotify/callback',
        ]);

        $this->actingAs(User::factory()->create());
    }

    public function test_guests_cannot_start_the_connection(): void
    {
        auth()->logout();

        $this->get(route('spotify.connect'))->assertRedirect(route('login'));
        $this->get(route('spotify.callback'))->assertRedirect(route('login'));
        $this->delete(route('spotify.disconnect'))->assertRedirect(route('login'));
    }

    public function test_it_sends_the_user_to_spotify(): void
    {
        $response = $this->get(route('spotify.connect'));

        $response->assertRedirectContains('accounts.spotify.com/authorize');
        $response->assertRedirectContains('client_id=test-id');
        $response->assertRedirectContains('user-read-currently-playing');
        // Ohne den State könnte jemand eine fremde Rückleitung unterschieben.
        $this->assertNotEmpty(session('spotify.state'));
    }

    public function test_it_refuses_without_credentials(): void
    {
        config(['services.spotify.client_id' => null]);

        $this->get(route('spotify.connect'))
            ->assertRedirect(route('music'))
            ->assertSessionHas('spotify-error');
    }

    public function test_it_stores_the_tokens_after_a_successful_callback(): void
    {
        Http::fake(['accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'access-1',
            'refresh_token' => 'refresh-1',
            'expires_in' => 3600,
            'scope' => 'user-read-currently-playing',
        ])]);

        $this->withSession(['spotify.state' => 'geheimer-state'])
            ->get(route('spotify.callback', ['code' => 'der-code', 'state' => 'geheimer-state']))
            ->assertRedirect(route('music'))
            ->assertSessionHas('spotify-status');

        $token = SpotifyToken::current();

        $this->assertNotNull($token);
        $this->assertSame('refresh-1', $token->refresh_token);
        $this->assertSame('access-1', $token->access_token);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_it_rejects_a_callback_with_the_wrong_state(): void
    {
        $this->withSession(['spotify.state' => 'echter-state'])
            ->get(route('spotify.callback', ['code' => 'der-code', 'state' => 'untergeschoben']))
            ->assertRedirect(route('music'))
            ->assertSessionHas('spotify-error');

        $this->assertNull(SpotifyToken::current());
        Http::assertNothingSent();
    }

    public function test_it_rejects_a_callback_without_any_state(): void
    {
        $this->get(route('spotify.callback', ['code' => 'der-code', 'state' => 'irgendwas']))
            ->assertSessionHas('spotify-error');

        $this->assertNull(SpotifyToken::current());
    }

    public function test_it_reports_a_refusal_from_spotify(): void
    {
        $this->withSession(['spotify.state' => 'geheimer-state'])
            ->get(route('spotify.callback', ['error' => 'access_denied', 'state' => 'geheimer-state']))
            ->assertSessionHas('spotify-error');

        $this->assertNull(SpotifyToken::current());
    }

    public function test_it_reports_a_rejected_code(): void
    {
        Http::fake(['accounts.spotify.com/api/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Invalid authorization code',
        ], 400)]);

        $this->withSession(['spotify.state' => 'geheimer-state'])
            ->get(route('spotify.callback', ['code' => 'falsch', 'state' => 'geheimer-state']))
            ->assertSessionHas('spotify-error');

        $this->assertNull(SpotifyToken::current());
    }

    public function test_the_state_cannot_be_used_twice(): void
    {
        Http::fake(['accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'access-1',
            'refresh_token' => 'refresh-1',
            'expires_in' => 3600,
        ])]);

        $this->withSession(['spotify.state' => 'einmal'])
            ->get(route('spotify.callback', ['code' => 'code', 'state' => 'einmal']))
            ->assertSessionHas('spotify-status');

        // Der State wurde beim ersten Mal verbraucht.
        $this->get(route('spotify.callback', ['code' => 'code', 'state' => 'einmal']))
            ->assertSessionHas('spotify-error');
    }

    public function test_it_disconnects(): void
    {
        SpotifyToken::query()->create([
            'refresh_token' => 'refresh-1',
            'access_token' => 'access-1',
            'expires_at' => now()->addHour(),
        ]);

        $this->delete(route('spotify.disconnect'))
            ->assertRedirect(route('music'))
            ->assertSessionHas('spotify-status');

        $this->assertNull(SpotifyToken::current());
    }

    public function test_the_refresh_token_is_not_stored_in_plain_text(): void
    {
        SpotifyToken::query()->create([
            'refresh_token' => 'streng-geheim',
            'expires_at' => now()->addHour(),
        ]);

        $raw = DB::table('spotify_tokens')->value('refresh_token');

        $this->assertNotSame('streng-geheim', $raw);
        $this->assertStringNotContainsString('streng-geheim', (string) $raw);
    }

    public function test_the_music_page_is_reachable(): void
    {
        $this->get(route('music'))
            ->assertOk()
            ->assertSee('Musik')
            ->assertSee('Mit Spotify verbinden');
    }
}
