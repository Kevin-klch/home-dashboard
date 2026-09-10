<x-dash.widget :class="$class" title="Abfuhr" icon="trash" accent="slate"
               :poll="config('dashboard.waste.poll_seconds')">
    @if (! $configured)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="trash" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Abfuhrkalender nicht hinterlegt</p>
            <p class="text-xs text-slate-600"><code class="text-slate-500">WASTE_ICS_URL</code> eintragen</p>
        </div>
    @elseif ($unreachable)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="trash" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Kalender nicht erreichbar</p>
            <p class="text-xs text-slate-600">Wird automatisch erneut versucht</p>
        </div>
    @elseif ($days === [])
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="trash" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Keine Termine bekannt</p>
        </div>
    @else
        @php $next = $days[0]; @endphp

        {{-- Die nächste Abfuhr ist die eigentliche Aussage der Kachel --}}
        <div data-next="{{ $next['date']->toDateString() }}" class="mb-3">
            <p class="text-[0.65rem] font-semibold tracking-widest text-slate-600 uppercase">
                Nächste Abfuhr {{ $next['when'] }}
            </p>

            <div class="mt-1.5 space-y-1">
                @foreach ($next['types'] as $type)
                    <p class="flex items-center gap-2">
                        <span class="size-2.5 shrink-0 rounded-full {{ $type->colors()['dot'] }}"></span>
                        <span class="truncate text-lg leading-tight font-medium text-white">{{ $type->label() }}</span>
                    </p>
                @endforeach
            </div>

            <p class="mt-1 text-xs text-slate-500">{{ $next['label'] }}</p>
        </div>

        @if (count($days) > 1)
            <ul class="mt-auto space-y-1.5 border-t border-white/5 pt-3">
                @foreach (array_slice($days, 1, 3) as $day)
                    <li class="flex items-center gap-2 text-xs" data-day="{{ $day['date']->toDateString() }}">
                        <span class="flex shrink-0 gap-1">
                            @foreach ($day['types'] as $type)
                                <span class="size-2 rounded-full {{ $type->colors()['dot'] }}"></span>
                            @endforeach
                        </span>

                        <span class="truncate text-slate-400">
                            {{ collect($day['types'])->map->label()->join(', ') }}
                        </span>

                        <span class="ml-auto shrink-0 text-slate-600">{{ $day['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</x-dash.widget>
