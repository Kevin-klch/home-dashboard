<x-dash.widget :class="$class" title="Notizen" icon="note" accent="amber">
    <x-slot:action>
        <a href="{{ route('notes') }}" wire:navigate class="text-xs text-slate-500 transition hover:text-slate-300">
            {{ $total }} gesamt
        </a>
    </x-slot:action>

    @if ($notes->isEmpty())
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="note" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Keine Notizen</p>
        </div>
    @else
        <ul class="no-scrollbar space-y-2 overflow-y-auto">
            @foreach ($notes as $note)
                <li wire:key="note-{{ $note->id }}" data-note="{{ $note->id }}"
                    class="rounded-xl border border-white/5 bg-white/5 px-3 py-2.5">
                    <p class="line-clamp-2 text-sm leading-snug text-slate-200">{{ $note->body }}</p>

                    <p class="mt-1 flex items-center gap-1.5 text-[0.7rem] text-slate-500">
                        @if ($note->isPinned())
                            <x-dash.icon name="check-small" class="size-3 text-amber-300/80" />
                        @endif
                        {{ $note->updated_at->diffForHumans() }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-auto pt-3">
        <form wire:submit="add" class="flex items-center gap-2">
            <input type="text" wire:model="newNote"
                   placeholder="Notiz hinzufügen …"
                   maxlength="500" autocomplete="off"
                   class="min-w-0 flex-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-sm text-slate-100 placeholder:text-slate-600 focus:border-amber-400/50 focus:ring-0 focus:outline-hidden">

            <button type="submit"
                    class="shrink-0 rounded-lg bg-white/10 p-1.5 text-slate-300 transition hover:bg-amber-500/20 hover:text-amber-200">
                <x-dash.icon name="plus" class="size-4" />
            </button>
        </form>

        @error('newNote')
            <p class="mt-1.5 text-[0.7rem] text-rose-300">{{ $message }}</p>
        @enderror
    </div>
</x-dash.widget>
