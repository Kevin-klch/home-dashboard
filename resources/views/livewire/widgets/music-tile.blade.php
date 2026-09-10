@use('App\Services\Music\PlaybackStatus')

@php
    // Große Bühne nur, wenn die Kachel wirklich Fläche hat. Die Entscheidung
    // fällt serverseitig anhand der Rastergröße – der Browser könnte sie nicht
    // treffen, ohne dass Inhalt und Zeilenhöhe sich gegenseitig hochschaukeln.
    $stage = $this->roomy();
@endphp

<x-dash.widget :class="$class.' relative overflow-hidden'"
               title="Musik" icon="music" accent="emerald" :poll="$poll">

    @if ($playback->is(PlaybackStatus::Playing) && $track?->artworkUrl)
        {{-- Das Cover als weicher Farbschleier hinter allem --}}
        <img src="{{ $track->artworkUrl }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute inset-0 size-full scale-125 object-cover opacity-20 blur-3xl">
    @endif

    @if ($playback->is(PlaybackStatus::NotConfigured))
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="music" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Spotify nicht eingerichtet</p>
            <p class="text-xs text-slate-600">Zugangsdaten fehlen in der .env</p>
        </div>
    @elseif ($playback->is(PlaybackStatus::Disconnected))
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="music" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Noch nicht verbunden</p>
            <a href="{{ route('music') }}" wire:navigate
               class="text-xs text-emerald-300/90 underline underline-offset-2 hover:text-emerald-200">
                Auf der Musik-Seite verbinden
            </a>
        </div>
    @elseif ($playback->is(PlaybackStatus::Unavailable))
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="music" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Spotify nicht erreichbar</p>
            <p class="text-xs text-slate-600">Wird automatisch erneut versucht</p>
        </div>
    @elseif ($playback->is(PlaybackStatus::Idle))
        @if ($last)
            {{-- Kurze Stille, etwa beim Gerätewechsel: Titel bleibt stehen. --}}
            <div class="flex flex-1 items-center gap-4 opacity-60" data-state="pause" data-last>
                @if ($last->artworkUrl)
                    <img src="{{ $last->artworkUrl }}" alt=""
                         class="aspect-square size-14 shrink-0 rounded-lg object-cover grayscale">
                @else
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-white/5">
                        <x-dash.icon name="music" class="size-6 text-slate-600" />
                    </span>
                @endif

                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $last->title }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $last->artist }}</p>
                    <p class="mt-0.5 text-[0.7rem] text-slate-600">Zuletzt gespielt</p>
                </div>
            </div>
        @else
            <div class="flex flex-1 flex-col items-center justify-center gap-3 text-center" data-state="still">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/5">
                    <x-dash.icon name="music" class="size-7 text-slate-600" />
                </span>
                <p class="text-sm text-slate-400">Gerade läuft nichts</p>
            </div>
        @endif
    @else
        <div class="relative flex h-full min-h-0 flex-col" data-state="laeuft" data-stage="{{ $stage ? 'gross' : 'kompakt' }}">

            @if ($stage)
                {{--
                    Das Cover liegt absolut in seinem Feld: so füllt es den
                    übrigen Platz aus, trägt aber selbst nichts zur Höhe bei
                    und kann die Kachel nicht aufblähen.
                --}}
                <div class="relative min-h-0 flex-1">
                    @if ($track->artworkUrl)
                        <img src="{{ $track->artworkUrl }}" alt="" data-cover
                             class="absolute inset-0 m-auto max-h-full max-w-full rounded-xl object-contain shadow-2xl shadow-black/60">
                    @else
                        <span class="absolute inset-0 m-auto flex size-24 items-center justify-center rounded-xl bg-white/5">
                            <x-dash.icon name="music" class="size-10 text-slate-600" />
                        </span>
                    @endif
                </div>

                <div class="mt-3 shrink-0 text-center">
                    <p class="truncate text-base font-medium text-white" title="{{ $track->title }}">{{ $track->title }}</p>
                    <p class="truncate text-sm text-slate-400">{{ $track->artist }}</p>
                    @unless ($track->isPlaying)
                        <p class="text-[0.7rem] text-slate-600">Pausiert</p>
                    @endunless
                </div>
            @else
                <div class="flex min-h-0 shrink-0 items-center gap-4">
                    @if ($track->artworkUrl)
                        <img src="{{ $track->artworkUrl }}" alt="" data-cover
                             class="aspect-square size-14 shrink-0 rounded-lg object-cover shadow-lg shadow-black/40">
                    @else
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-white/5">
                            <x-dash.icon name="music" class="size-6 text-slate-600" />
                        </span>
                    @endif

                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white" title="{{ $track->title }}">{{ $track->title }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $track->artist }}</p>
                        @unless ($track->isPlaying)
                            <p class="mt-0.5 text-[0.7rem] text-slate-600">Pausiert</p>
                        @endunless
                    </div>
                </div>
            @endif

            {{-- Steuerung --}}
            <div class="mt-auto shrink-0 pt-3">
                @if ($notice)
                    <p class="mb-2 text-center text-[0.7rem] text-amber-300/90">{{ $notice }}</p>
                @elseunless ($canControl)
                    <p class="mb-2 text-center text-[0.7rem] text-amber-300/90">
                        Zum Steuern fehlt die Berechtigung
                    </p>
                @endunless

                <div class="mb-3 flex items-center justify-center gap-6">
                    <button type="button" wire:click="previous" @disabled(! $canControl)
                            title="Vorheriger Titel"
                            class="text-slate-400 transition hover:text-white disabled:opacity-30">
                        <x-dash.icon name="skip-previous" class="size-5" />
                    </button>

                    <button type="button" wire:click="togglePlay" @disabled(! $canControl)
                            title="{{ $track->isPlaying ? 'Pause' : 'Abspielen' }}"
                            class="flex size-10 items-center justify-center rounded-full bg-white text-slate-950 shadow-lg shadow-black/30 transition hover:scale-105 disabled:opacity-30 disabled:hover:scale-100">
                        <x-dash.icon :name="$track->isPlaying ? 'pause' : 'play'"
                                     class="{{ $track->isPlaying ? 'size-4' : 'size-4 translate-x-px' }}" />
                    </button>

                    <button type="button" wire:click="next" @disabled(! $canControl)
                            title="Nächster Titel"
                            class="text-slate-400 transition hover:text-white disabled:opacity-30">
                        <x-dash.icon name="skip-next" class="size-5" />
                    </button>
                </div>

                @if ($track->durationMs)
                    <div class="h-1 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-emerald-400 transition-all duration-1000"
                             style="width: {{ $track->progressPercent() }}%"></div>
                    </div>
                    <div class="mt-1.5 flex justify-between text-[0.65rem] tabular-nums text-slate-500">
                        <span>{{ $track->positionLabel() }}</span>
                        <span>{{ $track->durationLabel() }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-dash.widget>
