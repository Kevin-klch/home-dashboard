<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_the_dashboard_renders_every_widget(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk()
            ->assertSee('Wetter')
            ->assertSee('Einkaufsliste')
            ->assertSee('Notizen')
            ->assertSee('Termine')
            ->assertSee('Aufgaben')
            ->assertSee('WLAN');
    }

    public function test_the_dashboard_shows_the_side_navigation(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Kalender')
            ->assertSee('Smart Home')
            ->assertSee('Geplant');
    }

    public function test_users_can_log_out_from_the_side_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('layout.sidenav')
            ->call('logout')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
