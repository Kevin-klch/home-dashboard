@php
    $events = [
        ['time' => '09:00', 'until' => '09:30', 'title' => 'Daily Standup',        'accent' => 'bg-sky-400'],
        ['time' => '13:00', 'until' => '14:00', 'title' => 'Mittagessen mit Anna', 'accent' => 'bg-emerald-400'],
        ['time' => '16:30', 'until' => '17:15', 'title' => 'Zahnarzt',             'accent' => 'bg-rose-400'],
        ['time' => '19:00', 'until' => null,    'title' => 'Sport',                'accent' => 'bg-violet-400'],
    ];
@endphp

<x-dash.widget {{ $attributes }} title="Termine" icon="calendar" accent="sky">
    <x-slot:action>
        <span class="text-xs text-slate-500">{{ now()->translatedFormat('D, j.n.') }}</span>
    </x-slot:action>

    <ul class="space-y-3 overflow-y-auto">
        @foreach ($events as $event)
            <li class="flex gap-3">
                <span class="mt-1 w-1 shrink-0 rounded-full {{ $event['accent'] }}"></span>
                <div class="min-w-0">
                    <p class="truncate text-sm text-slate-200">{{ $event['title'] }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $event['time'] }}@if ($event['until']) – {{ $event['until'] }} @endif
                    </p>
                </div>
            </li>
        @endforeach
    </ul>

    <p class="mt-auto border-t border-white/5 pt-3 text-xs text-slate-500">
        Morgen: 2 Termine
    </p>
</x-dash.widget>
