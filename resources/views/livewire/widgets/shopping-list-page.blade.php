<div>
    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
        <form wire:submit="add" class="flex gap-3">
            <input type="text" wire:model="newItem"
                   placeholder="Was fehlt?"
                   maxlength="100" autocomplete="off"
                   class="min-w-0 flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-400/50 focus:ring-0 focus:outline-hidden">

            <button type="submit"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-500/90 px-5 py-2.5 text-sm font-medium text-slate-950 transition hover:bg-emerald-400">
                <x-dash.icon name="plus" class="size-4" />
                Hinzufügen
            </button>
        </form>

        @error('newItem')
            <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
        @enderror

        @if ($notice)
            <p class="mt-2 text-xs text-amber-300/90">{{ $notice }}</p>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="cart" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-slate-300">Die Liste ist leer</p>
            <p class="mt-1 text-sm text-slate-600">Trage oben ein, was fehlt.</p>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
            <ul class="divide-y divide-white/5">
                @foreach ($items as $item)
                    <li wire:key="item-{{ $item->id }}" data-item="{{ $item->name }}"
                        class="flex items-center gap-4 px-5 transition hover:bg-white/5">

                        <button type="button" wire:click="toggle({{ $item->id }})"
                                class="flex flex-1 items-center gap-4 py-4 text-left"
                                title="{{ $item->isDone() ? 'Wieder offen' : 'Erledigt' }}">
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded-md border transition',
                                'border-emerald-400/70 bg-emerald-400/20' => $item->isDone(),
                                'border-slate-600' => ! $item->isDone(),
                            ])>
                                @if ($item->isDone())
                                    <x-dash.icon name="check-small" class="size-3.5 text-emerald-300" />
                                @endif
                            </span>

                            <span @class([
                                'flex-1 truncate transition',
                                'text-slate-600 line-through' => $item->isDone(),
                                'text-slate-100' => ! $item->isDone(),
                            ])>{{ $item->name }}</span>

                            @if ($item->note)
                                <span class="shrink-0 text-sm text-slate-500">{{ $item->note }}</span>
                            @endif
                        </button>

                        <button type="button" wire:click="remove({{ $item->id }})"
                                wire:confirm="{{ $item->name }} entfernen?" title="Entfernen"
                                class="shrink-0 rounded-lg p-1.5 text-slate-700 transition hover:bg-white/5 hover:text-rose-300">
                            <x-dash.icon name="close" class="size-4" />
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
            <span>{{ $openCount }} offen · {{ $doneCount }} erledigt</span>

            @if ($doneCount > 0)
                <button type="button" wire:click="clearCompleted"
                        class="underline underline-offset-4 transition hover:text-slate-300">
                    Erledigte entfernen
                </button>
            @endif
        </div>
    @endif
</div>
