<div @if (config('dashboard.calendar.poll_seconds')) wire:poll.{{ config('dashboard.calendar.poll_seconds') }}s @endif>
    @if (! $configured)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="calendar" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Kalender noch nicht verbunden</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                Die geheime iCal-Adresse deines Kalenders gehört in
                <code class="text-slate-400">CALENDAR_ICS_URL</code>. In Google Kalender findest du sie
                unter Einstellungen → Kalender integrieren.
            </p>
        </div>
    @elseif ($events === null)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="calendar" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Kalender nicht erreichbar</p>
            <p class="mt-2 text-sm text-slate-500">Wird automatisch erneut versucht.</p>
        </div>
    @elseif ($events === [])
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="check" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">Nichts geplant</p>
            <p class="mt-2 text-sm text-slate-500">
                Keine Termine in den nächsten {{ $daysAhead }} Tagen.
            </p>
        </div>
    @else
        {{-- Vorher gruppieren statt im Markup Tags über Schleifen hinweg zu öffnen --}}
        @php $days = collect($events)->groupBy(fn ($event) => $event->dayKey()); @endphp

        <div class="space-y-6">
            @foreach ($days as $dayKey => $dayEvents)
                <section wire:key="day-{{ $dayKey }}">
                    <h2 class="mb-2 text-xs font-semibold tracking-widest text-slate-500 uppercase">
                        {{ $dayEvents->first()->dayLabel() }}
                    </h2>

                    <ul class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
                        @foreach ($dayEvents as $event)
                            <li data-event="{{ $dayKey }}"
                                class="flex items-center gap-4 border-b border-white/5 px-5 py-4 last:border-b-0">
                                <span @class([
                                    'h-10 w-1 shrink-0 rounded-full',
                                    'bg-emerald-400' => $event->allDay,
                                    'bg-sky-400' => ! $event->allDay,
                                ])></span>

                                <span class="w-28 shrink-0 text-sm tabular-nums text-slate-400">
                                    {{ $event->timeLabel() }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-slate-100">{{ $event->title }}</p>
                                    @if ($event->location)
                                        <p class="truncate text-xs text-slate-500">{{ $event->location }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <p class="mt-6 text-center text-xs text-slate-600">
            Nächste {{ $daysAhead }} Tage · {{ count($events) }} Termine
        </p>
    @endif
</div>
