<x-dashboard-layout>
    <header class="mb-7 flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-medium tracking-tight text-white">
                @php
                    $hour = (int) now()->format('G');
                    $greeting = match (true) {
                        $hour < 5  => 'Gute Nacht',
                        $hour < 11 => 'Guten Morgen',
                        $hour < 18 => 'Hallo',
                        default    => 'Guten Abend',
                    };
                @endphp
                {{ $greeting }}{{ auth()->user() ? ', '.Str::before(auth()->user()->name, ' ') : '' }}
            </h1>
            <p class="mt-1 text-sm text-slate-400">{{ now()->translatedFormat('l, j. F Y') }}</p>
        </div>

        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300">
            <span class="size-1.5 rounded-full bg-emerald-400"></span>
            Alle Geräte online
        </span>
    </header>

    {{--
        Das Raster kommt aus der Datenbank: welche Kachel wo und wie breit
        liegt, stellt man über den Knopf oben rechts im Raster selbst ein.
    --}}
    <livewire:dashboard-grid />
</x-dashboard-layout>
