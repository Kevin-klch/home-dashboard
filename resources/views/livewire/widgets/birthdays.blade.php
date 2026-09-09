<x-dash.widget
    :class="$class"
    title="Geburtstage"
    icon="cake"
    accent="rose"
    :poll="config('dashboard.birthdays.poll_seconds')"
>
    @if (! $configured)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="cake" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Noch nicht verbunden</p>
            <p class="text-xs text-slate-600">iCal-Adresse in <code class="text-slate-500">BIRTHDAYS_ICS_URL</code> eintragen</p>
        </div>
    @elseif ($unreachable)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="cake" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Kalender nicht erreichbar</p>
            <p class="text-xs text-slate-600">Wird automatisch erneut versucht</p>
        </div>
    @elseif ($today === [] && $upcoming === [])
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="cake" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Keine Geburtstage hinterlegt</p>
        </div>
    @elseif ($today !== [])
        {{-- Jemand hat heute Geburtstag – das ist die Hauptaussage der Kachel. --}}
        <div class="flex flex-1 flex-col items-center justify-center gap-3 text-center" data-state="heute">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-rose-500/15">
                <x-dash.icon name="cake" class="size-8 text-rose-300" />
            </span>

            @php
                // "Anna", "Anna und Ben", "Anna, Ben und Carla"
                $names = count($today) > 1
                    ? implode(', ', array_slice($today, 0, -1)).' und '.end($today)
                    : $today[0];
            @endphp

            <div>
                <p class="text-lg leading-tight font-medium text-white">{{ $names }}</p>
                <p class="mt-0.5 text-sm text-rose-300/90">
                    {{ count($today) > 1 ? 'haben' : 'hat' }} heute Geburtstag
                </p>
            </div>
        </div>
    @else
        {{-- Niemand heute – dann zählt, wer als Nächstes dran ist. --}}
        @php $next = $upcoming[0]; @endphp

        <div class="flex flex-1 flex-col justify-center" data-state="naechster">
            <p class="text-[0.65rem] font-semibold tracking-widest text-slate-600 uppercase">
                Nächster Geburtstag
            </p>

            <p class="mt-2 truncate text-2xl leading-tight font-light text-white">{{ $next['name'] }}</p>

            <p class="mt-1 text-sm text-rose-300/90">{{ $next['inLabel'] }}</p>

            {{-- translatedFormat statt format: sonst stünde hier "June" --}}
            <p class="text-xs text-slate-500">
                {{ $next['date']->isoFormat('dd') }},
                {{ $next['date']->translatedFormat($next['date']->isCurrentYear() ? 'j. F' : 'j. F Y') }}
            </p>
        </div>
    @endif

    {{-- Wer danach kommt, klein am Fuß – ohne den heutigen Anlass zu überdecken. --}}
    @if ($configured && ! $unreachable)
        @php $later = $today !== [] ? array_slice($upcoming, 0, 2) : array_slice($upcoming, 1, 2); @endphp

        @if ($later !== [])
            <ul class="mt-auto space-y-1 border-t border-white/5 pt-3">
                @foreach ($later as $entry)
                    <li class="flex items-baseline gap-2 text-xs" data-later="{{ $entry['name'] }}">
                        <span class="truncate text-slate-400">{{ $entry['name'] }}</span>
                        <span class="ml-auto shrink-0 text-slate-600">{{ $entry['inLabel'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</x-dash.widget>
