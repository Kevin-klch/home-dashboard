<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PIN = '48190273';

    protected function setUp(): void
    {
        parent::setUp();

        config(['dashboard.security.pin_length' => 8]);
        RateLimiter::clear('pin-login|127.0.0.1');
    }

    private function userWithPin(string $pin = self::PIN): User
    {
        return User::factory()->create(['pin' => $pin]);
    }

    public function test_the_login_screen_shows_a_pin_pad(): void
    {
        $this->userWithPin();

        $response = $this->get(route('login'));

        $response->assertOk();

        foreach (range(0, 9) as $digit) {
            $response->assertSee('data-digit="'.$digit.'"', escape: false);
        }

        $response->assertSee('data-action="backspace"', escape: false);
    }

    public function test_the_pad_is_collected_in_the_browser(): void
    {
        // Eine Anfrage pro Anmeldung statt einer pro Ziffer – auf einem
        // einprozessigen Server macht das den Unterschied.
        $this->userWithPin();

        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('x-on:click="press(', $html);
        $this->assertStringContainsString('$wire.submit(this.pin)', $html);
        $this->assertStringNotContainsString('wire:click="press', $html);
    }

    public function test_it_signs_in_with_the_correct_pin(): void
    {
        $user = $this->userWithPin();

        Volt::test('pages.auth.login')
            ->call('submit', self::PIN)
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_pin_is_refused(): void
    {
        $this->userWithPin();

        Volt::test('pages.auth.login')
            ->call('submit', '00011122')
            ->assertHasErrors('form.pin')
            ->assertSet('form.pin', '');

        $this->assertGuest();
    }

    public function test_an_incomplete_pin_is_refused(): void
    {
        // Der Wert kommt aus dem Browser und wird serverseitig geprüft.
        $this->userWithPin();

        Volt::test('pages.auth.login')
            ->call('submit', '481')
            ->assertHasErrors(['form.pin' => 'digits']);

        $this->assertGuest();
    }

    public function test_anything_that_is_not_digits_is_refused(): void
    {
        $this->userWithPin();

        foreach (['4819027a', '', '481902 3'] as $rubbish) {
            Volt::test('pages.auth.login')
                ->call('submit', $rubbish)
                ->assertHasErrors('form.pin');
        }

        $this->assertGuest();
    }

    public function test_it_locks_out_after_too_many_attempts(): void
    {
        config(['dashboard.security.pin_attempts' => 3]);
        $this->userWithPin();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            Volt::test('pages.auth.login')->call('submit', '00011122');
        }

        // Auch die richtige PIN kommt jetzt nicht mehr durch.
        $component = Volt::test('pages.auth.login')->call('submit', self::PIN);

        $component->assertHasErrors('form.pin');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Zu viele Fehlversuche',
            $component->errors()->first('form.pin')
        );
    }

    public function test_a_successful_login_clears_the_lockout_counter(): void
    {
        $this->userWithPin();

        Volt::test('pages.auth.login')->call('submit', '00011122');
        Volt::test('pages.auth.login')->call('submit', self::PIN);

        $this->assertFalse(RateLimiter::tooManyAttempts('pin-login|127.0.0.1', 1));
    }

    public function test_the_pin_is_stored_hashed(): void
    {
        $user = $this->userWithPin();

        $this->assertNotSame(self::PIN, $user->pin);
        $this->assertTrue(Hash::check(self::PIN, $user->pin));
    }

    public function test_it_signs_in_the_right_person_when_several_have_a_pin(): void
    {
        $this->userWithPin('11122233');
        $second = $this->userWithPin('33344455');

        Volt::test('pages.auth.login')->call('submit', '33344455');

        $this->assertAuthenticatedAs($second);
    }

    public function test_users_without_a_pin_cannot_be_reached_this_way(): void
    {
        User::factory()->create(['pin' => null]);

        $this->get(route('login'))->assertSee('noch keine PIN eingerichtet');
    }

    public function test_the_password_login_stays_reachable(): void
    {
        $this->userWithPin();

        $this->get(route('login'))->assertSee(route('login.password'), escape: false);
        $this->get(route('login.password'))->assertOk()->assertSee('Email');
    }

    public function test_a_pin_login_is_remembered(): void
    {
        // Das Tablet soll sich nicht wieder abmelden.
        $user = $this->userWithPin();

        Volt::test('pages.auth.login')->call('submit', self::PIN);

        $this->assertNotNull($user->fresh()->remember_token);
    }
}
