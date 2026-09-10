<div @if (config('dashboard.waste.poll_seconds')) wire:poll.{{ config('dashboard.waste.poll_seconds') }}s @endif>
    @if (! $configured)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="trash" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Abfuhrkalender noch nicht hinterlegt</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                Den ICS-Feed deines Entsorgers in <code class="text-slate-400">WASTE_ICS_URL</code> eintragen.
                Für Moers liefert ENNI ihn pro Straße unter
                <code class="text-slate-400">abfallkalender.enni.de/ics-kalender/&lt;strasse&gt;</code>.
            </p>
        </div>
    @elseif ($unreachable)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="trash" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Kalender nicht erreichbar</p>
            <p class="mt-2 text-sm text-slate-500">Wird automatisch erneut versucht.</p>
        </div>
    @elseif ($days === [])
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="trash" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Keine Termine bekannt</p>
            <p class="mt-2 text-sm text-slate-500">Der Feed enthält gerade keine kommenden Abfuhren.</p>
        </div>
    @else
        @php $next = $days[0]; @endphp

        {{-- Herausgehoben: was als Nächstes rausmuss --}}
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl"
             data-next="{{ $next['date']->toDateString() }}">
            <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">
                Nächste Abfuhr {{ $next['when'] }}
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                @foreach ($next['types'] as $type)
                    <span class="inline-flex items-center gap-2 rounded-xl px-3 py-2 {{ $type->colors()['chip'] }}">
                        <span class="size-3 rounded-full {{ $type->colors()['dot'] }}"></span>
                        <span class="text-lg font-medium">{{ $type->label() }}</span>
                    </span>
                @endforeach
            </div>

            <p class="mt-3 text-sm text-slate-400">
                {{ $next['date']->isoFormat('dddd') }}, {{ $next['date']->translatedFormat('j. F Y') }}
            </p>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
            <ul class="divide-y divide-white/5">
                @foreach (array_slice($days, 1) as $day)
                    <li class="flex items-center gap-4 px-5 py-4" data-day="{{ $day['date']->toDateString() }}">
                        <span class="w-24 shrink-0">
                            <span class="block text-sm text-slate-200">{{ $day['label'] }}</span>
                            <span class="block text-[0.7rem] text-slate-600">{{ $day['when'] }}</span>
                        </span>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($day['types'] as $type)
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs {{ $type->colors()['chip'] }}">
                                    <span class="size-2 rounded-full {{ $type->colors()['dot'] }}"></span>
                                    {{ $type->label() }}
                                </span>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="mt-6 text-center text-xs text-slate-600">
            {{ count($days) }} Abfuhrtage · Daten vom Entsorger
        </p>
    @endif
</div>
