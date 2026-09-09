@php
    $visibleHours = max(1, (int) config('dashboard.weather.visible_hours'));
@endphp

<x-dash.widget
    :class="$class"
    title="Wetter"
    :icon="$snapshot?->condition->icon() ?? 'cloud'"
    accent="amber"
    :poll="config('dashboard.weather.poll_seconds')"
>
    <x-slot:action>
        <span class="text-xs text-slate-500">{{ config('dashboard.weather.location') }}</span>
    </x-slot:action>

    @if ($snapshot === null)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="cloud" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Wetterdaten nicht verfügbar</p>
            <p class="text-xs text-slate-600">Wird automatisch erneut versucht</p>
        </div>
    @else
        <div class="flex min-h-0 flex-1 gap-5">

            {{-- Links: aktuelle Lage und die Stundenleiste --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-5xl font-light tracking-tight text-white">
                            {{ $snapshot->temperature }}<span class="text-2xl text-slate-400">°</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-400">{{ $snapshot->condition->label() }}</p>
                    </div>

                    <dl class="space-y-1 text-right text-xs text-slate-500">
                        @if ($snapshot->apparentTemperature !== null)
                            <div><dt class="inline">Gefühlt</dt> <dd class="inline text-slate-300">{{ $snapshot->apparentTemperature }}°</dd></div>
                        @endif
                        @if ($snapshot->high !== null)
                            <div><dt class="inline">Max</dt> <dd class="inline text-slate-300">{{ $snapshot->high }}°</dd></div>
                        @endif
                        @if ($snapshot->low !== null)
                            <div><dt class="inline">Min</dt> <dd class="inline text-slate-300">{{ $snapshot->low }}°</dd></div>
                        @endif
                    </dl>
                </div>

                @if ($snapshot->hours !== [])
                    <div class="mt-auto border-t border-white/5 pt-4">
                        <div class="relative">
                            {{--
                                Waagerecht scrollbar: sichtbar sind so viele Spalten, wie in der
                                Konfiguration stehen, gewischt werden kann über alle Stunden.
                                Die Breite kommt als Inline-Stil, weil Tailwind dynamisch
                                zusammengesetzte Klassennamen nicht findet.
                            --}}
                            <div class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
                                @foreach ($snapshot->hours as $hour)
                                    <div class="flex shrink-0 snap-start flex-col items-center gap-1 px-1"
                                         style="width: calc(100% / {{ $visibleHours }})"
                                         data-hour="{{ $hour->shortLabel() }}">
                                        <span class="text-[0.65rem] text-slate-500">{{ $hour->shortLabel() }}</span>
                                        <x-dash.icon :name="$hour->condition->icon()" class="size-5 text-amber-300/70" />
                                        <span class="text-sm tabular-nums text-slate-200">{{ $hour->temperature }}°</span>
                                        {{-- Feste Höhe, damit die Spalten auch ohne Regenangabe bündig bleiben --}}
                                        <span class="h-3 text-[0.6rem] leading-3 text-sky-300/80">
                                            @if ($hour->precipitationProbability !== null && $hour->precipitationProbability >= 30){{ $hour->precipitationProbability }}%@endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Verlauf am rechten Rand als Hinweis, dass es weitergeht --}}
                            <div class="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-slate-900/80 to-transparent"></div>
                        </div>

                        <p class="mt-2 text-[0.6rem] text-slate-600">
                            {{ min($visibleHours, count($snapshot->hours)) }} von {{ count($snapshot->hours) }} Stunden · seitlich wischen
                        </p>
                    </div>
                @endif
            </div>

            {{-- Rechts: kompakte Wochenvorschau --}}
            @if ($snapshot->days !== [])
                <div class="flex w-40 shrink-0 flex-col border-l border-white/5 pl-4">
                    <ul class="flex flex-1 flex-col justify-between gap-0.5">
                        @foreach ($snapshot->days as $day)
                            <li data-day="{{ $day->date->toDateString() }}"
                                @class([
                                    'flex items-center gap-2 rounded-lg px-2 py-1',
                                    'bg-white/5' => $day->date->isToday(),
                                ])>
                                <span @class([
                                    'w-6 shrink-0 text-xs',
                                    'font-medium text-white' => $day->date->isToday(),
                                    'text-slate-400' => ! $day->date->isToday(),
                                ])>{{ $day->weekday() }}</span>

                                <x-dash.icon :name="$day->condition->icon()" class="size-4 shrink-0 text-amber-300/70" />

                                <span class="ml-auto text-xs tabular-nums">
                                    <span class="text-slate-100">{{ $day->high }}°</span><span class="text-slate-600">/{{ $day->low }}°</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-2 text-[0.6rem] text-slate-600">Open-Meteo</p>
                </div>
            @endif
        </div>
    @endif
</x-dash.widget>
