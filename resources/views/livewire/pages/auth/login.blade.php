<?php

use App\Livewire\Forms\PinLoginForm;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public PinLoginForm $form;

    public function length(): int
    {
        return max(4, min(10, (int) config('dashboard.security.pin_length')));
    }

    /** Ist überhaupt eine PIN eingerichtet? */
    public function pinAvailable(): bool
    {
        return User::query()->whereNotNull('pin')->exists();
    }

    /**
     * Die vollständige PIN prüfen.
     *
     * Die Eingabe sammelt der Browser; hierher kommt sie erst, wenn alle
     * Stellen getippt sind. Ein Roundtrip statt acht – auf einem einprozessigen
     * Entwicklungsserver ist das der Unterschied zwischen zäh und flüssig.
     */
    public function submit(string $pin = ''): void
    {
        $this->form->pin = $pin;

        // Was der Browser schickt, wird hier trotzdem geprüft.
        $this->validate(
            ['form.pin' => ['required', 'digits:'.$this->length()]],
            [
                'form.pin.required' => 'Bitte die PIN eingeben.',
                'form.pin.digits' => 'Die PIN besteht aus '.$this->length().' Ziffern.',
            ],
        );

        try {
            $this->form->authenticate();
        } catch (ValidationException $e) {
            $this->form->pin = '';

            throw $e;
        }

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="w-full">
    <x-auth-session-status class="mb-6 text-center text-sm text-emerald-300" :status="session('status')" />

    @if (! $this->pinAvailable())
        <div class="rounded-2xl border border-amber-400/20 bg-amber-500/10 p-5 text-center">
            <p class="text-sm text-amber-200">Es ist noch keine PIN eingerichtet.</p>
            <p class="mt-2 text-xs text-amber-200/70">
                Melde dich mit E-Mail und Passwort an und lege sie im Profil fest.
            </p>
        </div>
    @else
        {{--
            Die Eingabe lebt im Browser. Erst die letzte Ziffer löst eine
            Serveranfrage aus – vorher gibt es keine Wartezeit pro Tastendruck.
        --}}
        <div x-data="{
                 pin: '',
                 length: @js($this->length()),
                 busy: false,

                 async press(digit) {
                     if (this.busy || this.pin.length >= this.length) return

                     this.pin += digit

                     if (this.pin.length === this.length) {
                         this.busy = true
                         await $wire.submit(this.pin)
                         this.pin = ''
                         this.busy = false
                     }
                 },

                 back() {
                     if (! this.busy) this.pin = this.pin.slice(0, -1)
                 },
             }"
             x-on:keydown.window="
                 if ($event.key >= '0' && $event.key <= '9') press($event.key)
                 else if ($event.key === 'Backspace') back()
             ">

            <div class="flex justify-center gap-3" role="status">
                <template x-for="stelle in length" :key="stelle">
                    <span class="size-3.5 rounded-full transition"
                          :class="stelle <= pin.length ? 'bg-sky-400' : 'bg-white/15'"></span>
                </template>
            </div>

            <p class="mt-4 h-5 text-center text-sm text-rose-300">
                <span x-show="busy" class="text-slate-500">Prüfe …</span>
                <span x-show="! busy">{{ $errors->first('form.pin') }}</span>
            </p>

            <div class="mt-4 grid grid-cols-3 gap-3" :class="busy && 'pointer-events-none opacity-60'">
                @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
                    <button type="button" x-on:click="press('{{ $digit }}')" data-digit="{{ $digit }}"
                            class="flex h-16 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-2xl font-light text-slate-100 transition active:scale-95 active:bg-white/15 hover:bg-white/10">
                        {{ $digit }}
                    </button>
                @endforeach

                <span></span>

                <button type="button" x-on:click="press('0')" data-digit="0"
                        class="flex h-16 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-2xl font-light text-slate-100 transition active:scale-95 active:bg-white/15 hover:bg-white/10">
                    0
                </button>

                <button type="button" x-on:click="back()" data-action="backspace"
                        aria-label="Letzte Stelle löschen"
                        class="flex h-16 items-center justify-center rounded-2xl text-slate-400 transition active:scale-95 hover:bg-white/5 hover:text-slate-100">
                    <x-dash.icon name="backspace" class="size-6" />
                </button>
            </div>
        </div>
    @endif

    <p class="mt-8 text-center">
        <a href="{{ route('login.password') }}" wire:navigate
           class="text-xs text-slate-500 underline underline-offset-4 transition hover:text-slate-300">
            Mit E-Mail anmelden
        </a>
    </p>
</div>
