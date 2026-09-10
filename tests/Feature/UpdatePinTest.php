<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UpdatePinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['dashboard.security.pin_length' => 8]);
    }

    public function test_a_pin_can_be_set(): void
    {
        $user = User::factory()->create(['pin' => null]);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->set('pin', '48190273')
            ->set('pin_confirmation', '48190273')
            ->call('updatePin')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('48190273', $user->fresh()->pin));
    }

    public function test_the_repetition_must_match(): void
    {
        $user = User::factory()->create(['pin' => null]);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->set('pin', '48190273')
            ->set('pin_confirmation', '48190274')
            ->call('updatePin')
            ->assertHasErrors(['pin' => 'confirmed']);

        $this->assertNull($user->fresh()->pin);
    }

    public function test_the_length_is_enforced(): void
    {
        $user = User::factory()->create(['pin' => null]);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->set('pin', '4819')
            ->set('pin_confirmation', '4819')
            ->call('updatePin')
            ->assertHasErrors(['pin' => 'digits']);
    }

    public function test_letters_are_refused(): void
    {
        $user = User::factory()->create(['pin' => null]);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->set('pin', '4819027a')
            ->set('pin_confirmation', '4819027a')
            ->call('updatePin')
            ->assertHasErrors(['pin' => 'digits']);
    }

    /**
     * Das Erste, was jemand ausprobiert.
     */
    public function test_obvious_pins_are_refused(): void
    {
        $user = User::factory()->create(['pin' => null]);

        foreach (['00000000', '11111111', '12345678', '98765432'] as $weak) {
            Volt::actingAs($user)
                ->test('profile.update-pin-form')
                ->set('pin', $weak)
                ->set('pin_confirmation', $weak)
                ->call('updatePin')
                ->assertHasErrors(['pin' => 'not_in']);
        }

        $this->assertNull($user->fresh()->pin);
    }

    public function test_a_pin_can_be_removed(): void
    {
        $user = User::factory()->create(['pin' => '48190273']);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->call('removePin');

        $this->assertNull($user->fresh()->pin);
    }

    public function test_the_form_appears_on_the_profile_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile'))
            ->assertOk()
            ->assertSeeVolt('profile.update-pin-form')
            ->assertSee('PIN fürs Wandtablet');
    }

    public function test_the_input_is_cleared_after_saving(): void
    {
        $user = User::factory()->create(['pin' => null]);

        Volt::actingAs($user)
            ->test('profile.update-pin-form')
            ->set('pin', '48190273')
            ->set('pin_confirmation', '48190273')
            ->call('updatePin')
            ->assertSet('pin', '')
            ->assertSet('pin_confirmation', '');
    }
}
