<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jeder Punkt der Seitennavigation mit seiner Route.
     *
     * @return array<string, array{string, string}>
     */
    public static function navigationItems(): array
    {
        return [
            'Dashboard' => ['dashboard', 'Dashboard'],
            'Einkaufsliste' => ['shopping', 'Einkaufsliste'],
            'Aufgaben' => ['tasks', 'Aufgaben'],
            'Kalender' => ['calendar', 'Kalender'],
            'Notizen' => ['notes', 'Notizen'],
            'Musik' => ['music', 'Musik'],
        ];
    }

    #[DataProvider('navigationItems')]
    public function test_every_navigation_item_has_a_working_page(string $route, string $heading): void
    {
        $this->fakeOpenMeteo();
        $this->actingAs(User::factory()->create());

        $this->get(route($route))
            ->assertOk()
            ->assertSee($heading);
    }

    #[DataProvider('navigationItems')]
    public function test_every_page_requires_a_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    public function test_the_sidebar_links_to_every_page(): void
    {
        $this->fakeOpenMeteo();
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboard'));

        foreach (self::navigationItems() as [$route, $label]) {
            $response->assertSee(route($route), escape: false);
            $response->assertSee($label);
        }
    }

    #[DataProvider('navigationItems')]
    public function test_the_current_page_is_marked_in_the_sidebar(string $route): void
    {
        $this->fakeOpenMeteo();
        $this->actingAs(User::factory()->create());

        $html = $this->get(route($route))->getContent();

        // Genau ein Punkt darf als aktuell markiert sein.
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_planned_sections_stay_unclickable(): void
    {
        $this->fakeOpenMeteo();
        $this->actingAs(User::factory()->create());

        $html = $this->get(route('dashboard'))->getContent();

        // Smart Home und Klima sind angekündigt, aber noch ohne Seite.
        $this->assertStringContainsString('Smart Home', $html);
        $this->assertStringContainsString('Klima', $html);
        $this->assertMatchesRegularExpression('/cursor-not-allowed[^>]*>\s*<svg/s', $html);
    }
}
