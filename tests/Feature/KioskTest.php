<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Was das Dashboard braucht, um dauerhaft an der Wand zu hängen.
 */
class KioskTest extends TestCase
{
    use RefreshDatabase;

    public function test_staying_signed_in_is_preselected(): void
    {
        // Ein Wandtablet soll sich nicht alle paar Stunden abmelden.
        Volt::test('pages.auth.password-login')->assertSet('form.remember', true);

        // Der Haken muss auch sichtbar gesetzt sein, nicht nur im Zustand.
        preg_match('/<input[^>]*id="remember"[^>]*>/', $this->get(route('login.password'))->getContent(), $input);

        $this->assertNotEmpty($input, 'Das Feld "Angemeldet bleiben" fehlt.');
        $this->assertStringContainsString('checked', $input[0]);
    }

    public function test_a_login_sets_the_long_lived_remember_cookie(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.password-login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_the_session_outlasts_a_single_evening(): void
    {
        // 120 Minuten wären der Laravel-Standard – zu wenig für den Flur.
        $this->assertGreaterThanOrEqual(43200, (int) config('session.lifetime'));
        $this->assertFalse((bool) config('session.expire_on_close'));
    }

    public function test_the_layout_offers_itself_as_a_home_screen_app(): void
    {
        $this->fakeDashboardSources();

        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('rel="manifest"', $html);
        $this->assertStringContainsString('rel="apple-touch-icon"', $html);
        $this->assertStringContainsString('apple-mobile-web-app-capable', $html);
        $this->assertStringContainsString('name="theme-color"', $html);
    }

    public function test_the_manifest_and_icons_exist(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertNotEmpty($manifest['icons']);

        foreach (['apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512] as $file => $size) {
            $path = public_path("icons/{$file}");

            $this->assertFileExists($path);

            [$width, $height] = getimagesize($path);
            $this->assertSame($size, $width, "{$file} hat die falsche Breite.");
            $this->assertSame($size, $height, "{$file} hat die falsche Höhe.");
        }
    }

    public function test_every_icon_referenced_by_the_manifest_is_present(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }
}
