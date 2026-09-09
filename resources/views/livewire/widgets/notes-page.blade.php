<div>
    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
        <form wire:submit="add" class="flex flex-col gap-3">
            <textarea wire:model="newNote" rows="3" maxlength="500"
                      placeholder="Was soll nicht vergessen werden?"
                      class="w-full resize-y rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm leading-relaxed text-slate-100 placeholder:text-slate-600 focus:border-amber-400/50 focus:ring-0 focus:outline-hidden"></textarea>

            <div class="flex items-center justify-between">
                @error('newNote')
                    <p class="text-xs text-rose-300">{{ $message }}</p>
                @else
                    <span class="text-xs text-slate-600">Höchstens 500 Zeichen</span>
                @enderror

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500/90 px-5 py-2.5 text-sm font-medium text-slate-950 transition hover:bg-amber-400">
                    <x-dash.icon name="plus" class="size-4" />
                    Notiz anlegen
                </button>
            </div>
        </form>
    </div>

    @if ($notes->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="note" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-slate-300">Noch keine Notizen</p>
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($notes as $note)
                <div wire:key="note-{{ $note->id }}" data-note="{{ $note->id }}"
                     @class([
                         'flex flex-col rounded-2xl border p-4 backdrop-blur-xl transition',
                         'border-amber-400/30 bg-amber-500/5' => $note->isPinned(),
                         'border-white/10 bg-white/5' => ! $note->isPinned(),
                     ])>

                    @if ($editingId === $note->id)
                        <form wire:submit="save" class="flex flex-1 flex-col gap-2">
                            <textarea wire:model="editingBody" rows="4" maxlength="500"
                                      class="w-full flex-1 resize-y rounded-lg border border-white/10 bg-slate-950/40 px-3 py-2 text-sm leading-relaxed text-slate-100 focus:border-amber-400/50 focus:ring-0 focus:outline-hidden"></textarea>

                            @error('editingBody')
                                <p class="text-[0.7rem] text-rose-300">{{ $message }}</p>
                            @enderror

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        class="rounded-lg bg-amber-500/90 px-3 py-1.5 text-xs font-medium text-slate-950 transition hover:bg-amber-400">
                                    Speichern
                                </button>
                                <button type="button" wire:click="cancel"
                                        class="text-xs text-slate-500 transition hover:text-slate-300">
                                    Abbrechen
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="flex-1 text-sm leading-relaxed whitespace-pre-line text-slate-200">{{ $note->body }}</p>

                        <div class="mt-4 flex items-center gap-1 border-t border-white/5 pt-3">
                            <span class="mr-auto text-[0.7rem] text-slate-600">
                                {{ $note->updated_at->diffForHumans() }}
                            </span>

                            <button type="button" wire:click="togglePin({{ $note->id }})"
                                    title="{{ $note->isPinned() ? 'Nicht mehr anheften' : 'Anheften' }}"
                                    @class([
                                        'rounded-lg p-1.5 transition hover:bg-white/5',
                                        'text-amber-300' => $note->isPinned(),
                                        'text-slate-600 hover:text-slate-300' => ! $note->isPinned(),
                                    ])>
                                <x-dash.icon name="check-small" class="size-4" />
                            </button>

                            <button type="button" wire:click="edit({{ $note->id }})" title="Bearbeiten"
                                    class="rounded-lg p-1.5 text-slate-600 transition hover:bg-white/5 hover:text-slate-300">
                                <x-dash.icon name="note" class="size-4" />
                            </button>

                            <button type="button" wire:click="remove({{ $note->id }})"
                                    wire:confirm="Diese Notiz löschen?" title="Löschen"
                                    class="rounded-lg p-1.5 text-slate-600 transition hover:bg-white/5 hover:text-rose-300">
                                <x-dash.icon name="close" class="size-4" />
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
