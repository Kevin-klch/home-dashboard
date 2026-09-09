<x-dash.widget :class="$class" title="Aufgaben" icon="check" accent="violet">
    <x-slot:action>
        <span class="text-xs text-slate-500">{{ $openCount }} offen</span>
    </x-slot:action>

    @if ($tasks->isEmpty())
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="check" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Nichts zu tun</p>
        </div>
    @else
        <ul class="no-scrollbar -mx-1 space-y-0.5 overflow-y-auto">
            @foreach ($tasks as $task)
                <li wire:key="task-{{ $task->id }}" data-task="{{ $task->name }}">
                    <button type="button" wire:click="toggle({{ $task->id }})"
                            class="flex w-full items-center gap-3 rounded-lg px-1 py-1.5 text-left transition hover:bg-white/5">
                        <span @class([
                            'flex size-4 shrink-0 items-center justify-center rounded border transition',
                            'border-violet-400/70 bg-violet-400/20' => $task->isDone(),
                            'border-slate-600' => ! $task->isDone(),
                        ])>
                            @if ($task->isDone())
                                <x-dash.icon name="check-small" class="size-3 text-violet-300" />
                            @endif
                        </span>

                        <span @class([
                            'truncate text-sm transition',
                            'text-slate-600 line-through' => $task->isDone(),
                            'text-slate-200' => ! $task->isDone(),
                        ])>{{ $task->name }}</span>

                        @if ($task->dueLabel() && ! $task->isDone())
                            <span @class([
                                'ml-auto shrink-0 rounded-md px-1.5 py-0.5 text-[0.7rem]',
                                'bg-rose-500/15 text-rose-300' => $task->isUrgent(),
                                'text-slate-500' => ! $task->isUrgent(),
                            ])>{{ $task->dueLabel() }}</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-auto pt-3">
        <form wire:submit="add" class="mb-3 flex items-center gap-2">
            <input type="text" wire:model="newTask"
                   placeholder="Aufgabe hinzufügen …"
                   maxlength="100" autocomplete="off"
                   class="min-w-0 flex-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-sm text-slate-100 placeholder:text-slate-600 focus:border-violet-400/50 focus:ring-0 focus:outline-hidden">

            <button type="submit"
                    class="shrink-0 rounded-lg bg-white/10 p-1.5 text-slate-300 transition hover:bg-violet-500/20 hover:text-violet-200">
                <x-dash.icon name="plus" class="size-4" />
            </button>
        </form>

        @error('newTask')
            <p class="mb-2 text-[0.7rem] text-rose-300">{{ $message }}</p>
        @enderror

        @if ($tasks->isNotEmpty())
            <div class="h-1 overflow-hidden rounded-full bg-white/5">
                <div class="h-full rounded-full bg-violet-400/70" style="width: {{ $progress }}%"></div>
            </div>
        @endif
    </div>
</x-dash.widget>
