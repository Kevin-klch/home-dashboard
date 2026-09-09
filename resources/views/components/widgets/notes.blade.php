@php
    $notes = [
        ['text' => 'WLAN-Passwort Gäste steht im Flurschrank',    'age' => 'vor 2 Tagen'],
        ['text' => 'Ersatzschlüssel bei Familie Weber',            'age' => 'letzte Woche'],
        ['text' => 'Heizung Wartungstermin im Oktober vereinbaren','age' => 'letzte Woche'],
    ];
@endphp

<x-dash.widget {{ $attributes }} title="Notizen" icon="note" accent="amber">
    <x-slot:action>
        <button type="button" class="rounded-lg p-1 text-slate-500 transition hover:bg-white/5 hover:text-slate-200">
            <x-dash.icon name="plus" class="size-4" />
        </button>
    </x-slot:action>

    <ul class="space-y-2 overflow-y-auto">
        @foreach ($notes as $note)
            <li class="rounded-xl border border-white/5 bg-white/5 px-3 py-2.5">
                <p class="text-sm leading-snug text-slate-200">{{ $note['text'] }}</p>
                <p class="mt-1 text-[0.7rem] text-slate-500">{{ $note['age'] }}</p>
            </li>
        @endforeach
    </ul>
</x-dash.widget>
