<x-dash.widget
    :class="$class"
    title="Termine"
    icon="calendar"
    accent="sky"
    :poll="config('dashboard.calendar.poll_seconds')"
>
    <x-slot:action>
        <span class="text-xs text-slate-500">{{ now()->isoFormat('dd') }}, {{ now()->format('j.n.') }}</span>
    </x-slot:action>

    @if (! $configured)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="calendar" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Kalender noch nicht verbunden</p>
            <p class="text-xs text-slate-600">iCal-Adresse in <code class="text-slate-500">CALENDAR_ICS_URL</code> eintragen</p>
        </div>
    @elseif ($events === null)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="calendar" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Kalender nicht erreichbar</p>
            <p class="text-xs text-slate-600">Wird automatisch erneut versucht</p>
        </div>
    @elseif ($events === [])
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="check" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Nichts geplant</p>
            <p class="text-xs text-slate-600">Keine Termine in den nächsten {{ config('dashboard.calendar.days_ahead') }} Tagen</p>
        </div>
    @else
        @php $currentDay = null; @endphp

        <ul class="no-scrollbar space-y-2 overflow-y-auto">
            @foreach ($events as $event)
                @if ($event->dayKey() !== $currentDay)
                    @php $currentDay = $event->dayKey(); @endphp
                    <li class="pt-2 text-[0.65rem] font-semibold tracking-widest text-slate-600 uppercase first:pt-0">
                        {{ $event->dayLabel() }}
                    </li>
                @endif

                <li class="flex gap-3" data-event="{{ $event->dayKey() }}">
                    <span @class([
                        'mt-1 w-1 shrink-0 rounded-full',
                        'bg-emerald-400' => $event->allDay,
                        'bg-sky-400' => ! $event->allDay,
                    ])></span>

                    <div class="min-w-0">
                        <p class="truncate text-sm text-slate-200">{{ $event->title }}</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $event->timeLabel() }}@if ($event->location) · {{ $event->location }} @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-dash.widget>
