@php
    // Dummy-Daten – spaeter aus der Datenbank.
    $items = [
        ['name' => 'Milch',        'note' => '2 l',        'done' => false],
        ['name' => 'Kaffeebohnen', 'note' => '1 kg',       'done' => false],
        ['name' => 'Tomaten',      'note' => null,         'done' => false],
        ['name' => 'Olivenöl',     'note' => null,         'done' => true],
        ['name' => 'Spülmaschinentabs', 'note' => null,    'done' => true],
    ];
@endphp

<x-dash.widget {{ $attributes }} title="Einkaufsliste" icon="cart" accent="emerald">
    <x-slot:action>
        <button type="button" class="rounded-lg p-1 text-slate-500 transition hover:bg-white/5 hover:text-slate-200">
            <x-dash.icon name="plus" class="size-4" />
        </button>
    </x-slot:action>

    <ul class="-mx-1 space-y-0.5 overflow-y-auto">
        @foreach ($items as $item)
            <li x-data="{ done: @js($item['done']) }">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-1 py-2 transition hover:bg-white/5">
                    <input type="checkbox" x-model="done"
                           class="size-4 shrink-0 rounded-sm border-slate-600 bg-white/5 text-emerald-500 focus:ring-emerald-500/40 focus:ring-offset-0">
                    <span class="text-sm transition" :class="done ? 'text-slate-600 line-through' : 'text-slate-200'">
                        {{ $item['name'] }}
                    </span>
                    @if ($item['note'])
                        <span class="ml-auto text-xs text-slate-500">{{ $item['note'] }}</span>
                    @endif
                </label>
            </li>
        @endforeach
    </ul>

    <p class="mt-auto border-t border-white/5 pt-3 text-xs text-slate-500">
        {{ collect($items)->where('done', false)->count() }} von {{ count($items) }} offen
    </p>
</x-dash.widget>
