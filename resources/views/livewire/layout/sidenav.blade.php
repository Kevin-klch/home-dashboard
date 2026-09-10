<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    // Platzhalter-Navigation. Die Ziele zeigen bewusst noch alle auf das Dashboard –
    // eigene Routen kommen, sobald es die jeweiligen Seiten gibt.
    $nav = [
        ['label' => 'Dashboard',     'icon' => 'squares',  'route' => 'dashboard'],
        ['label' => 'Einkaufsliste', 'icon' => 'cart',     'route' => 'shopping'],
        ['label' => 'Aufgaben',      'icon' => 'check',    'route' => 'tasks'],
        ['label' => 'Kalender',      'icon' => 'calendar', 'route' => 'calendar'],
        ['label' => 'Notizen',       'icon' => 'note',     'route' => 'notes'],
        ['label' => 'Musik',         'icon' => 'music',    'route' => 'music'],
        ['label' => 'Abfuhr',        'icon' => 'trash',    'route' => 'waste'],
        ['label' => 'WLAN',          'icon' => 'wifi',     'route' => 'wifi'],
    ];

    $comingSoon = [
        ['label' => 'Smart Home', 'icon' => 'home'],
        ['label' => 'Klima',      'icon' => 'thermometer'],
    ];
@endphp

<aside class="flex w-60 shrink-0 flex-col border-r border-white/5 bg-slate-900/60 backdrop-blur-xl">
    {{-- Kopf --}}
    <div class="flex items-center gap-3 px-5 py-6">
        <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 shadow-lg shadow-sky-500/20">
            <x-dash.icon name="home" class="size-5 text-white" />
        </div>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-white">{{ config('app.name') }}</p>
            <p class="text-xs text-slate-400">Zuhause</p>
        </div>
    </div>

    {{-- Hauptnavigation --}}
    <nav class="flex-1 space-y-1 px-3">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']); @endphp

            <a href="{{ route($item['route']) }}" wire:navigate
               @class([
                   'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
                   'bg-white/10 font-medium text-white shadow-xs' => $active,
                   'text-slate-400 hover:bg-white/5 hover:text-slate-100' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                <x-dash.icon :name="$item['icon']" class="size-5 shrink-0" />
                <span>{{ $item['label'] }}</span>
                @if ($active)
                    <span class="ml-auto size-1.5 rounded-full bg-sky-400"></span>
                @endif
            </a>
        @endforeach

        <p class="px-3 pt-6 pb-2 text-[0.65rem] font-semibold tracking-widest text-slate-600 uppercase">
            Geplant
        </p>

        @foreach ($comingSoon as $item)
            <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600">
                <x-dash.icon :name="$item['icon']" class="size-5 shrink-0" />
                <span>{{ $item['label'] }}</span>
            </span>
        @endforeach
    </nav>

    {{-- Fuss: Benutzer --}}
    <div class="border-t border-white/5 p-3">
        <div class="flex items-center gap-3 rounded-xl px-3 py-2.5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-medium text-slate-200">
                {{ Str::of(auth()->user()?->name ?? 'Gast')->substr(0, 1)->upper() }}
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm text-slate-200">{{ auth()->user()?->name ?? 'Gast' }}</p>
                <a href="{{ route('profile') }}" wire:navigate class="text-xs text-slate-500 hover:text-slate-300">Profil</a>
            </div>
            <button type="button" title="Nachtmodus umschalten"
                    x-on:click="window.dispatchEvent(new CustomEvent('nachtmodus-umschalten'))"
                    data-nachtmodus
                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-white/5 hover:text-slate-200">
                <x-dash.icon name="moon" class="size-5" />
            </button>

            <button type="button" wire:click="logout" title="Abmelden"
                    class="rounded-lg p-1.5 text-slate-500 transition hover:bg-white/5 hover:text-slate-200">
                <x-dash.icon name="logout" class="size-5" />
            </button>
        </div>
    </div>
</aside>
