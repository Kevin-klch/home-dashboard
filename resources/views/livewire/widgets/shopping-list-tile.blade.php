<x-dash.widget :class="$class" title="Einkaufsliste" icon="cart" accent="emerald">
    <x-slot:action>
        <span class="text-xs text-slate-500">
            {{ $openCount }} {{ $openCount === 1 ? 'Eintrag' : 'Einträge' }}
        </span>
    </x-slot:action>

    @if ($items->isEmpty())
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="cart" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Liste ist leer</p>
        </div>
    @else
        <ul class="no-scrollbar -mx-1 space-y-0.5 overflow-y-auto">
            @foreach ($items as $item)
                <li wire:key="item-{{ $item->id }}"
                    data-item="{{ $item->name }}"
                    class="group flex items-center gap-2 rounded-lg px-1 transition hover:bg-white/5">

                    <button type="button" wire:click="toggle({{ $item->id }})"
                            class="flex flex-1 items-center gap-3 py-2 text-left"
                            title="{{ $item->isDone() ? 'Wieder offen' : 'Erledigt' }}">
                        <span @class([
                            'flex size-4 shrink-0 items-center justify-center rounded border transition',
                            'border-emerald-400/70 bg-emerald-400/20' => $item->isDone(),
                            'border-slate-600' => ! $item->isDone(),
                        ])>
                            @if ($item->isDone())
                                <x-dash.icon name="check-small" class="size-3 text-emerald-300" />
                            @endif
                        </span>

                        <span @class([
                            'truncate text-sm transition',
                            'text-slate-600 line-through' => $item->isDone(),
                            'text-slate-200' => ! $item->isDone(),
                        ])>{{ $item->name }}</span>

                        @if ($item->note)
                            <span class="ml-auto shrink-0 text-xs text-slate-500">{{ $item->note }}</span>
                        @endif
                    </button>

                    <button type="button" wire:click="remove({{ $item->id }})"
                            wire:confirm="{{ $item->name }} entfernen?"
                            title="Entfernen"
                            class="shrink-0 rounded p-1 text-slate-700 opacity-60 transition hover:bg-white/5 hover:text-rose-300 hover:opacity-100">
                        <x-dash.icon name="close" class="size-3.5" />
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-auto border-t border-white/5 pt-3">
        <form wire:submit="add" class="flex items-center gap-2">
            <input type="text" wire:model="newItem"
                   placeholder="Hinzufügen …"
                   maxlength="100"
                   autocomplete="off"
                   class="min-w-0 flex-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-400/50 focus:ring-0 focus:outline-hidden">

            <button type="submit"
                    class="shrink-0 rounded-lg bg-white/10 p-1.5 text-slate-300 transition hover:bg-emerald-500/20 hover:text-emerald-200">
                <x-dash.icon name="plus" class="size-4" />
            </button>
        </form>

        @error('newItem')
            <p class="mt-1.5 text-[0.7rem] text-rose-300">{{ $message }}</p>
        @enderror

        @if ($notice)
            <p class="mt-1.5 text-[0.7rem] text-amber-300/90">{{ $notice }}</p>
        @endif

        @if ($doneCount > 0)
            <button type="button" wire:click="clearCompleted"
                    class="mt-2 text-[0.7rem] text-slate-600 underline underline-offset-2 transition hover:text-slate-400">
                {{ $doneCount }} {{ $doneCount === 1 ? 'erledigten Eintrag' : 'erledigte Einträge' }} entfernen
            </button>
        @endif
    </div>
</x-dash.widget>
