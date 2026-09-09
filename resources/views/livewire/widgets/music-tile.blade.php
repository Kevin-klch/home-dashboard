@use('App\Services\Music\PlaybackStatus')

<x-dash.widget :class="$class" title="Musik" icon="music" accent="emerald" :poll="$poll">
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
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center" data-state="still">
            <x-dash.icon name="music" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">Gerade läuft nichts</p>
        </div>
    @else
        <div class="flex h-full flex-col" data-state="laeuft">
            <div class="flex min-h-0 items-center gap-4">
                @if ($track->artworkUrl)
                    <img src="{{ $track->artworkUrl }}" alt=""
                         class="size-14 shrink-0 rounded-lg object-cover shadow-lg shadow-black/40">
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

            <div class="mt-auto pt-3">
                @if ($notice)
                    <p class="mb-2 text-center text-[0.7rem] text-amber-300/90">{{ $notice }}</p>
                @elseunless ($canControl)
                    <p class="mb-2 text-center text-[0.7rem] text-amber-300/90">
                        Zum Steuern fehlt die Berechtigung
                    </p>
                @endunless

                <div class="mb-3 flex items-center justify-center gap-5">
                    <button type="button" wire:click="previous" @disabled(! $canControl)
                            title="Vorheriger Titel"
                            class="text-slate-400 transition hover:text-white disabled:opacity-30">
                        <x-dash.icon name="skip-previous" class="size-5" />
                    </button>

                    <button type="button" wire:click="togglePlay" @disabled(! $canControl)
                            title="{{ $track->isPlaying ? 'Pause' : 'Abspielen' }}"
                            class="flex size-9 items-center justify-center rounded-full bg-white text-slate-950 transition hover:scale-105 disabled:opacity-30 disabled:hover:scale-100">
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
                    <div class="h-1 overflow-hidden rounded-full bg-white/5">
                        <div class="h-full rounded-full bg-emerald-400/70"
                             style="width: {{ $track->progressPercent() }}%"></div>
                    </div>
                    <div class="mt-1.5 flex justify-between text-[0.65rem] tabular-nums text-slate-600">
                        <span>{{ $track->positionLabel() }}</span>
                        <span>{{ $track->durationLabel() }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-dash.widget>
