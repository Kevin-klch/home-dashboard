<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $pin = '';

    public string $pin_confirmation = '';

    public function length(): int
    {
        return max(4, min(10, (int) config('dashboard.security.pin_length')));
    }

    public function hasPin(): bool
    {
        return auth()->user()->hasPin();
    }

    public function updatePin(): void
    {
        $length = $this->length();

        $validated = $this->validate(
            [
                'pin' => ['required', 'digits:'.$length, 'confirmed', Rule::notIn($this->weakPins())],
                'pin_confirmation' => ['required'],
            ],
            [
                'pin.required' => 'Bitte eine PIN eingeben.',
                'pin.digits' => "Die PIN muss genau {$length} Ziffern haben.",
                'pin.confirmed' => 'Die Wiederholung stimmt nicht überein.',
                'pin.not_in' => 'Diese PIN ist zu leicht zu erraten.',
            ],
        );

        auth()->user()->update(['pin' => $validated['pin']]);

        $this->reset('pin', 'pin_confirmation');

        $this->dispatch('pin-updated');
    }

    public function removePin(): void
    {
        auth()->user()->update(['pin' => null]);

        $this->dispatch('pin-removed');
    }

    /**
     * Offensichtliches aussperren.
     *
     * Eine PIN aus lauter gleichen Ziffern oder eine simple Zahlenreihe ist
     * das Erste, was jemand ausprobiert.
     *
     * @return list<string>
     */
    private function weakPins(): array
    {
        $length = $this->length();
        $weak = [];

        foreach (range(0, 9) as $digit) {
            $weak[] = str_repeat((string) $digit, $length);
        }

        $ascending = '0123456789';
        $weak[] = substr($ascending, 0, $length);
        $weak[] = substr(strrev($ascending), 0, $length);
        $weak[] = substr('123456789', 0, $length);

        return $weak;
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            PIN fürs Wandtablet
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Mit der PIN meldest du dich am Dashboard an, ohne E-Mail und Passwort zu tippen.
            Sie besteht aus {{ $this->length() }} Ziffern und wird verschlüsselt gespeichert.
        </p>
    </header>

    <form wire:submit="updatePin" class="mt-6 space-y-6">
        <div>
            <x-input-label for="pin" value="Neue PIN" />
            <x-text-input wire:model="pin" id="pin" name="pin" type="password"
                          inputmode="numeric" autocomplete="new-password"
                          maxlength="{{ $this->length() }}"
                          class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="pin_confirmation" value="PIN wiederholen" />
            <x-text-input wire:model="pin_confirmation" id="pin_confirmation" name="pin_confirmation"
                          type="password" inputmode="numeric" autocomplete="new-password"
                          maxlength="{{ $this->length() }}"
                          class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('pin_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Speichern</x-primary-button>

            @if ($this->hasPin())
                <button type="button" wire:click="removePin"
                        wire:confirm="PIN entfernen? Die Anmeldung geht dann nur noch über E-Mail und Passwort."
                        class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    PIN entfernen
                </button>
            @endif

            <x-action-message class="me-3" on="pin-updated">Gespeichert.</x-action-message>
            <x-action-message class="me-3" on="pin-removed">Entfernt.</x-action-message>
        </div>
    </form>
</section>
