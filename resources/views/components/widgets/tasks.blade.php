@php
    $tasks = [
        ['name' => 'Müll rausbringen',      'due' => 'Heute',    'urgent' => true,  'done' => false],
        ['name' => 'Rechnung Stadtwerke',   'due' => 'Morgen',   'urgent' => false, 'done' => false],
        ['name' => 'Pflanzen gießen',       'due' => 'Fr',       'urgent' => false, 'done' => false],
        ['name' => 'Fahrrad aufpumpen',     'due' => null,       'urgent' => false, 'done' => true],
    ];
    $open = collect($tasks)->where('done', false)->count();
    $progress = count($tasks) > 0 ? (int) round((count($tasks) - $open) / count($tasks) * 100) : 0;
@endphp

<x-dash.widget {{ $attributes }} title="Aufgaben" icon="check" accent="violet">
    <x-slot:action>
        <span class="text-xs text-slate-500">{{ $open }} offen</span>
    </x-slot:action>

    <ul class="-mx-1 space-y-0.5">
        @foreach ($tasks as $task)
            <li x-data="{ done: @js($task['done']) }">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-1 py-1.5 transition hover:bg-white/5">
                    <input type="checkbox" x-model="done"
                           class="size-4 shrink-0 rounded-sm border-slate-600 bg-white/5 text-violet-500 focus:ring-violet-500/40 focus:ring-offset-0">
                    <span class="truncate text-sm transition" :class="done ? 'text-slate-600 line-through' : 'text-slate-200'">
                        {{ $task['name'] }}
                    </span>
                    @if ($task['due'])
                        <span @class([
                            'ml-auto shrink-0 rounded-md px-1.5 py-0.5 text-[0.7rem]',
                            'bg-rose-500/15 text-rose-300' => $task['urgent'],
                            'text-slate-500' => ! $task['urgent'],
                        ])>{{ $task['due'] }}</span>
                    @endif
                </label>
            </li>
        @endforeach
    </ul>

    <div class="mt-auto pt-4">
        <div class="h-1 overflow-hidden rounded-full bg-white/5">
            <div class="h-full rounded-full bg-violet-400/70" style="width: {{ $progress }}%"></div>
        </div>
    </div>
</x-dash.widget>
