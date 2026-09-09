@php
    // Dummy-Daten – spaeter aus einer Wetter-API.
    $current = ['temp' => 18, 'feels' => 16, 'condition' => 'Leicht bewölkt', 'high' => 21, 'low' => 11];
    $forecast = [
        ['time' => '15 Uhr', 'temp' => 19, 'icon' => 'sun'],
        ['time' => '18 Uhr', 'temp' => 17, 'icon' => 'sun'],
        ['time' => '21 Uhr', 'temp' => 13, 'icon' => 'clock'],
        ['time' => '00 Uhr', 'temp' => 11, 'icon' => 'clock'],
    ];
@endphp

<x-dash.widget {{ $attributes }} title="Wetter" icon="sun" accent="amber">
    <x-slot:action>
        <span class="text-xs text-slate-500">Köln</span>
    </x-slot:action>

    <div class="flex items-start justify-between">
        <div>
            <p class="text-5xl font-light tracking-tight text-white">{{ $current['temp'] }}<span class="text-2xl text-slate-400">°</span></p>
            <p class="mt-1 text-sm text-slate-400">{{ $current['condition'] }}</p>
        </div>
        <dl class="space-y-1 text-right text-xs text-slate-500">
            <div><dt class="inline">Gefühlt</dt> <dd class="inline text-slate-300">{{ $current['feels'] }}°</dd></div>
            <div><dt class="inline">Max</dt> <dd class="inline text-slate-300">{{ $current['high'] }}°</dd></div>
            <div><dt class="inline">Min</dt> <dd class="inline text-slate-300">{{ $current['low'] }}°</dd></div>
        </dl>
    </div>

    <div class="mt-auto grid grid-cols-4 gap-2 border-t border-white/5 pt-4">
        @foreach ($forecast as $hour)
            <div class="flex flex-col items-center gap-1.5">
                <span class="text-[0.7rem] text-slate-500">{{ $hour['time'] }}</span>
                <x-dash.icon :name="$hour['icon']" class="size-5 text-amber-300/70" />
                <span class="text-sm text-slate-200">{{ $hour['temp'] }}°</span>
            </div>
        @endforeach
    </div>
</x-dash.widget>
