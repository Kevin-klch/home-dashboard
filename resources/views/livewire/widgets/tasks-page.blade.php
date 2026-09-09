<div>
    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
        <form wire:submit="add" class="flex flex-col gap-3 sm:flex-row">
            <input type="text" wire:model="newTask"
                   placeholder="Was ist zu tun?"
                   maxlength="100" autocomplete="off"
                   class="min-w-0 flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-slate-100 placeholder:text-slate-600 focus:border-violet-400/50 focus:ring-0 focus:outline-hidden">

            <input type="date" wire:model="newDueOn"
                   title="Fällig am (optional)"
                   class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-slate-300 focus:border-violet-400/50 focus:ring-0 focus:outline-hidden">

            <button type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-500/90 px-5 py-2.5 text-sm font-medium text-slate-950 transition hover:bg-violet-400">
                <x-dash.icon name="plus" class="size-4" />
                Hinzufügen
            </button>
        </form>

        @error('newTask')
            <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
        @enderror
        @error('newDueOn')
            <p class="mt-2 text-xs text-rose-300">{{ $message }}</p>
        @enderror
    </div>

    @if ($tasks->isEmpty())
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="check" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-slate-300">Nichts zu tun</p>
            <p class="mt-1 text-sm text-slate-600">Trage oben die erste Aufgabe ein.</p>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
            <ul class="divide-y divide-white/5">
                @foreach ($tasks as $task)
                    <li wire:key="task-{{ $task->id }}" data-task="{{ $task->name }}"
                        class="group flex items-center gap-4 px-5 transition hover:bg-white/5">

                        <button type="button" wire:click="toggle({{ $task->id }})"
                                class="flex flex-1 items-center gap-4 py-4 text-left"
                                title="{{ $task->isDone() ? 'Wieder offen' : 'Erledigt' }}">
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded-md border transition',
                                'border-violet-400/70 bg-violet-400/20' => $task->isDone(),
                                'border-slate-600' => ! $task->isDone(),
                            ])>
                                @if ($task->isDone())
                                    <x-dash.icon name="check-small" class="size-3.5 text-violet-300" />
                                @endif
                            </span>

                            <span @class([
                                'flex-1 truncate transition',
                                'text-slate-600 line-through' => $task->isDone(),
                                'text-slate-100' => ! $task->isDone(),
                            ])>{{ $task->name }}</span>

                            @if ($task->dueLabel())
                                <span @class([
                                    'shrink-0 rounded-lg px-2 py-1 text-xs',
                                    'bg-rose-500/15 text-rose-300' => $task->isUrgent(),
                                    'text-slate-500' => ! $task->isUrgent(),
                                    'line-through opacity-50' => $task->isDone(),
                                ])>{{ $task->dueLabel() }}</span>
                            @endif
                        </button>

                        <button type="button" wire:click="remove({{ $task->id }})"
                                wire:confirm="{{ $task->name }} löschen?"
                                title="Löschen"
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
