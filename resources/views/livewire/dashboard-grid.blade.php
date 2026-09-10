@php
    // Vollständige Klassennamen, damit der Tailwind-Scanner sie findet.
    $spans = [2 => 'col-span-2', 3 => 'col-span-3', 4 => 'col-span-4', 6 => 'col-span-6'];
    $rows = [1 => 'row-span-1', 2 => 'row-span-2', 3 => 'row-span-3'];
@endphp

<div>
    <div class="mb-4 flex items-center justify-end gap-2">
        @if ($arranging)
            <button type="button" wire:click="resetLayout"
                    wire:confirm="Anordnung auf den Auslieferungszustand zurücksetzen?"
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-400 transition hover:bg-white/10 hover:text-slate-200">
                Zurücksetzen
            </button>
        @endif

        <button type="button" wire:click="toggleArranging" data-arrange
                title="{{ $arranging ? 'Anordnen beenden' : 'Kacheln anordnen' }}"
                @class([
                    'rounded-full border p-2 transition',
                    'border-sky-400/40 bg-sky-500/20 text-sky-200' => $arranging,
                    'border-white/10 bg-white/5 text-slate-400 hover:bg-white/10 hover:text-slate-100' => ! $arranging,
                ])>
            <x-dash.icon :name="$arranging ? 'check-small' : 'cog'" class="size-4" />
        </button>
    </div>

    {{--
        Feste Grundhöhe je Zeile: ohne die richtet sich die Zeilenhöhe nach
        dem Inhalt, und eine Kachel über zwei Zeilen wäre nicht vorhersagbar.
        Nach oben bleibt sie offen, damit nichts abgeschnitten wird.
    --}}
    <div class="grid auto-rows-[minmax(12rem,auto)] grid-cols-6 gap-5">
        @foreach ($placed as $index => $tile)
            @php
                $widget = $registry->get($tile->widget);
                $span = $spans[$tile->width] ?? 'col-span-2';
                $row = $rows[$tile->height] ?? 'row-span-1';
                $widths = $registry->widths($tile->widget);
                $heights = $registry->heights($tile->widget);
            @endphp

            <div wire:key="tile-{{ $tile->widget }}" data-tile="{{ $tile->widget }}"
                 class="{{ $span }} {{ $row }} relative">

                {{-- Im Bearbeitungsmodus liegt eine Bedienebene über der Kachel --}}
                @if ($arranging)
                    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3
                                rounded-2xl border-2 border-dashed border-sky-400/40 bg-slate-950/80 backdrop-blur-sm">
                        <p class="text-sm font-medium text-slate-200">{{ $widget['label'] }}</p>

                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="moveEarlier('{{ $tile->widget }}')"
                                    @disabled($index === 0)
                                    data-move-earlier="{{ $tile->widget }}" title="Nach vorn"
                                    class="rounded-lg bg-white/10 p-2 text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                <x-dash.icon name="skip-previous" class="size-4" />
                            </button>

                            <button type="button" wire:click="moveLater('{{ $tile->widget }}')"
                                    @disabled($index === $placed->count() - 1)
                                    data-move-later="{{ $tile->widget }}" title="Nach hinten"
                                    class="rounded-lg bg-white/10 p-2 text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                <x-dash.icon name="skip-next" class="size-4" />
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center gap-1">
                                <span class="w-12 text-right text-[0.7rem] text-slate-500">Breite</span>

                                <button type="button" wire:click="narrow('{{ $tile->widget }}')"
                                        @disabled($tile->width <= min($widths))
                                        data-narrow="{{ $tile->widget }}" title="Schmaler"
                                        class="rounded-lg bg-white/10 px-2.5 py-1.5 text-sm text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                    −
                                </button>

                                <span class="w-6 text-center text-xs text-slate-400" data-width="{{ $tile->width }}">
                                    {{ $tile->width }}
                                </span>

                                <button type="button" wire:click="widen('{{ $tile->widget }}')"
                                        @disabled($tile->width >= max($widths))
                                        data-widen="{{ $tile->widget }}" title="Breiter"
                                        class="rounded-lg bg-white/10 px-2.5 py-1.5 text-sm text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                    +
                                </button>
                            </div>

                            <div class="flex items-center gap-1">
                                <span class="w-12 text-right text-[0.7rem] text-slate-500">Höhe</span>

                                <button type="button" wire:click="shorter('{{ $tile->widget }}')"
                                        @disabled($tile->height <= min($heights))
                                        data-shorter="{{ $tile->widget }}" title="Niedriger"
                                        class="rounded-lg bg-white/10 px-2.5 py-1.5 text-sm text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                    −
                                </button>

                                <span class="w-6 text-center text-xs text-slate-400" data-height="{{ $tile->height }}">
                                    {{ $tile->height }}
                                </span>

                                <button type="button" wire:click="taller('{{ $tile->widget }}')"
                                        @disabled($tile->height >= max($heights))
                                        data-taller="{{ $tile->widget }}" title="Höher"
                                        class="rounded-lg bg-white/10 px-2.5 py-1.5 text-sm text-slate-200 transition hover:bg-white/20 disabled:opacity-25">
                                    +
                                </button>
                            </div>
                        </div>

                        <button type="button" wire:click="remove('{{ $tile->widget }}')"
                                data-remove="{{ $tile->widget }}"
                                class="text-xs text-slate-500 underline underline-offset-4 transition hover:text-rose-300">
                            Entfernen
                        </button>
                    </div>
                @endif

                @if ($widget['livewire'])
                    <livewire:dynamic-component :component="$widget['component']"
                                                :key="'w-'.$tile->widget.'-'.$tile->width.'x'.$tile->height"
                                                :cols="$tile->width"
                                                :rows="$tile->height"
                                                class="h-full" />
                @else
                    <x-dynamic-component :component="$widget['component']" class="h-full" />
                @endif
            </div>
        @endforeach
    </div>

    @if ($arranging)
        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5">
            <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">
                Verfügbare Kacheln
            </p>

            @if ($available === [])
                <p class="mt-3 text-sm text-slate-500">Alle Kacheln liegen bereits auf dem Dashboard.</p>
            @else
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($available as $key => $widget)
                        <button type="button" wire:click="add('{{ $key }}')" data-add="{{ $key }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">
                            <x-dash.icon :name="$widget['icon']" class="size-4" />
                            {{ $widget['label'] }}
                            <span class="text-slate-600">+</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
