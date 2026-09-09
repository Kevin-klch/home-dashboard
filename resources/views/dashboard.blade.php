<x-dashboard-layout>
    {{--
        Vordesign mit Dummy-Daten. Jedes Widget ist eine eigenstaendige Komponente unter
        resources/views/components/widgets/ und laesst sich spaeter einzeln durch eine
        Livewire-Komponente mit echten Daten ersetzen, ohne dieses Raster anzufassen.
    --}}
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

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300">
                <span class="size-1.5 rounded-full bg-emerald-400"></span>
                Alle Geräte online
            </span>
            <button type="button"
                    class="rounded-full border border-white/10 bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-slate-100"
                    title="Widgets anordnen (noch ohne Funktion)">
                <x-dash.icon name="cog" class="size-4" />
            </button>
        </div>
    </header>

    {{--
        6-Spalten-Raster: passt im iPad-Querformat in drei Kacheln pro Reihe auf.
        Groesse und Position eines Widgets steckt allein in den col-/row-span-Klassen,
        spaeter also aus der Datenbank steuerbar.
    --}}
    <div class="grid grid-cols-6 gap-5">
        <x-widgets.clock          class="col-span-2" />
        <livewire:widgets.weather class="col-span-4" />

        <x-widgets.shopping-list  class="col-span-2" />
        <x-widgets.calendar       class="col-span-2" />
        <x-widgets.tasks          class="col-span-2" />

        <x-widgets.wifi-qr        class="col-span-2" />
        <x-widgets.notes          class="col-span-4" />
    </div>

    <p class="mt-6 text-center text-xs text-slate-600">
        Wetter live von Open-Meteo · die übrigen Kacheln zeigen noch Beispieldaten.
    </p>
</x-dashboard-layout>
