<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Anmeldung am Wandtablet per PIN.
 *
 * Eine sechsstellige PIN ist schwächer als ein Passwort – deshalb greift hier
 * eine strenge Sperre: nach wenigen Fehlversuchen ist für eine Minute Schluss.
 * Durchprobieren dauert damit Wochen.
 */
class PinLoginForm extends Form
{
    public string $pin = '';

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = $this->matchingUser();

        if ($user === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.pin' => 'Falsche PIN.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Dauerhaft angemeldet: das Tablet hängt an der Wand.
        Auth::login($user, remember: true);
    }

    /**
     * Wer im Haushalt hat diese PIN?
     *
     * Die PIN identifiziert und authentifiziert zugleich – deshalb wird gegen
     * alle hinterlegten PINs geprüft. Im Haushalt sind das eine Handvoll.
     */
    private function matchingUser(): ?User
    {
        foreach (User::query()->whereNotNull('pin')->cursor() as $user) {
            if (Hash::check($this->pin, $user->pin)) {
                return $user;
            }
        }

        return null;
    }

    protected function ensureIsNotRateLimited(): void
    {
        $attempts = (int) config('dashboard.security.pin_attempts');

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $attempts)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.pin' => "Zu viele Fehlversuche. Bitte in {$seconds} Sekunden erneut versuchen.",
        ]);
    }

    /** Gesperrt wird pro Gerät, nicht pro Konto – die PIN nennt ja keinen Namen. */
    protected function throttleKey(): string
    {
        return 'pin-login|'.request()->ip();
    }
}
