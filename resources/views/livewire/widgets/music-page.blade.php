@use('App\Services\Music\PlaybackStatus')

<div class="flex min-h-0 flex-1 flex-col" @if ($poll) wire:poll.{{ $poll }}s @endif>

    @if ($playback->is(PlaybackStatus::NotConfigured) || $playback->is(PlaybackStatus::Disconnected))
        <div class="flex flex-1 items-center justify-center">
            <div class="max-w-md rounded-2xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur-xl">
                <x-dash.icon name="music" class="mx-auto size-10 text-slate-700" />

                @if ($playback->is(PlaybackStatus::NotConfigured))
                    <p class="mt-4 text-lg text-slate-200">Spotify ist noch nicht eingerichtet</p>
                    <p class="mt-2 text-sm text-slate-500">
                        <code class="text-slate-400">SPOTIFY_CLIENT_ID</code> und
                        <code class="text-slate-400">SPOTIFY_CLIENT_SECRET</code> in die
                        <code class="text-slate-400">.env</code> eintragen, als Weiterleitung
                        <code class="text-slate-400">{{ config('services.spotify.redirect') }}</code> hinterlegen.
                    </p>
                @else
                    <p class="mt-4 text-lg text-slate-200">Noch nicht mit Spotify verbunden</p>
                    <p class="mt-2 text-sm text-slate-500">Einmal anmelden genügt – danach bleibt die Verbindung bestehen.</p>

                    <a href="{{ route('spotify.connect') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-full bg-emerald-500 px-6 py-2.5 text-sm font-medium text-slate-950 transition hover:bg-emerald-400">
                        <x-dash.icon name="music" class="size-4" />
                        Mit Spotify verbinden
                    </a>

                    <p class="mt-4 text-xs text-slate-600">
                        Diese Seite muss dafür über <code class="text-slate-500">127.0.0.1</code> aufgerufen werden,
                        nicht über <code class="text-slate-500">localhost</code>.
                    </p>
                @endif
            </div>
        </div>
    @else

        {{-- Bühne: das Cover bekommt allen Platz, der übrig bleibt --}}
        <div class="flex min-h-0 flex-1 items-center justify-center py-4">
            @if ($playback->is(PlaybackStatus::Unavailable))
                <div class="text-center" data-state="stoerung">
                    <x-dash.icon name="music" class="mx-auto size-12 text-slate-700" />
                    <p class="mt-4 text-lg text-slate-300">Spotify antwortet gerade nicht</p>
                    <p class="mt-1 text-sm text-slate-600">Wird automatisch erneut versucht.</p>
                </div>
            @elseif ($playback->is(PlaybackStatus::Idle))
                <div class="text-center" data-state="still">
                    <x-dash.icon name="music" class="mx-auto size-12 text-slate-700" />
                    <p class="mt-4 text-lg text-slate-300">Gerade läuft nichts</p>
                    <p class="mt-1 text-sm text-slate-600">Starte etwas auf einem deiner Geräte.</p>
                </div>
            @else
                <div class="relative" data-state="laeuft">
                    {{-- Farbschimmer hinter dem Cover, wie in der Spotify-Vollbildansicht --}}
                    <div class="absolute -inset-8 -z-10 rounded-full bg-white/5 blur-3xl"></div>

                    @if ($track->artworkUrl)
                        <img src="{{ $track->artworkUrl }}" alt=""
                             class="aspect-square w-[min(48vh,26rem)] rounded-lg object-cover shadow-2xl shadow-black/70">
                    @else
                        <div class="flex aspect-square w-[min(48vh,26rem)] items-center justify-center rounded-lg bg-white/5">
                            <x-dash.icon name="music" class="size-20 text-slate-700" />
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Steuerleiste --}}
        <div class="mt-4 border-t border-white/5 pt-5">
            @if ($notice)
                <p class="mb-4 text-center text-xs text-amber-300/90">{{ $notice }}</p>
            @endif

            @unless ($canControl)
                <p class="mb-4 text-center text-xs text-amber-300/90">
                    Zum Steuern fehlt die Berechtigung –
                    <a href="{{ route('spotify.connect') }}" class="underline underline-offset-2">Verbindung erneuern</a>.
                </p>
            @endunless

            <div class="grid grid-cols-1 items-center gap-6 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">

                {{-- Links: Titel --}}
                <div class="flex min-w-0 items-center gap-3">
                    @if ($track?->artworkUrl)
                        <img src="{{ $track->artworkUrl }}" alt=""
                             class="size-12 shrink-0 rounded object-cover">
                    @endif

                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">{{ $track?->title ?? '—' }}</p>
                        <p class="truncate text-xs text-slate-400">
                            {{ $track?->artist ?? 'Nichts ausgewählt' }}
                            @if ($track?->album) · {{ $track->album }} @endif
                        </p>
                        @if ($track && ! $track->isPlaying)
                            <p class="text-[0.7rem] text-slate-600">Pausiert</p>
                        @endif
                    </div>
                </div>

                {{-- Mitte: Knöpfe und Fortschritt --}}
                <div class="flex flex-col items-center gap-3">
                    <div class="flex items-center gap-6">
                        <button type="button" wire:click="toggleShuffle" @disabled(! $canControl)
                                title="Zufallswiedergabe"
                                @class([
                                    'transition disabled:opacity-30',
                                    'text-emerald-400' => $track?->shuffle,
                                    'text-slate-400 hover:text-white' => ! $track?->shuffle,
                                ])>
                            <x-dash.icon name="shuffle" class="size-5" />
                        </button>

                        <button type="button" wire:click="previous" @disabled(! $canControl)
                                title="Vorheriger Titel"
                                class="text-slate-300 transition hover:text-white disabled:opacity-30">
                            <x-dash.icon name="skip-previous" class="size-6" />
                        </button>

                        <button type="button" wire:click="togglePlay" @disabled(! $canControl)
                                title="{{ $track?->isPlaying ? 'Pause' : 'Abspielen' }}"
                                class="flex size-14 items-center justify-center rounded-full bg-white text-slate-950 shadow-lg shadow-black/40 transition hover:scale-105 disabled:opacity-30 disabled:hover:scale-100">
                            <x-dash.icon :name="$track?->isPlaying ? 'pause' : 'play'"
                                         class="{{ $track?->isPlaying ? 'size-6' : 'size-6 translate-x-0.5' }}" />
                        </button>

                        <button type="button" wire:click="next" @disabled(! $canControl)
                                title="Nächster Titel"
                                class="text-slate-300 transition hover:text-white disabled:opacity-30">
                            <x-dash.icon name="skip-next" class="size-6" />
                        </button>

                        <button type="button" wire:click="cycleRepeat" @disabled(! $canControl)
                                title="Wiederholen"
                                @class([
                                    'transition disabled:opacity-30',
                                    'text-emerald-400' => $track && $track->repeat !== 'off',
                                    'text-slate-400 hover:text-white' => ! $track || $track->repeat === 'off',
                                ])>
                            <x-dash.icon :name="$track?->repeatsOne() ? 'repeat-one' : 'repeat'" class="size-5" />
                        </button>
                    </div>

                    {{-- Fortschritt: Klick springt an die Stelle --}}
                    <div class="flex w-full max-w-xl items-center gap-3">
                        <span class="w-10 text-right text-xs tabular-nums text-slate-500">
                            {{ $track?->positionLabel() ?? '--:--' }}
                        </span>

                        <div class="group h-1.5 flex-1 cursor-pointer rounded-full bg-white/10"
                             @if ($canControl && $track?->durationMs)
                                 x-on:click="$wire.seekToPercent(Math.round(($event.clientX - $el.getBoundingClientRect().left) / $el.offsetWidth * 100))"
                             @endif>
                            <div class="h-full rounded-full bg-slate-300 transition-all duration-1000 group-hover:bg-emerald-400"
                                 style="width: {{ $track?->progressPercent() ?? 0 }}%"></div>
                        </div>

                        <span class="w-10 text-xs tabular-nums text-slate-500">
                            {{ $track?->durationLabel() ?? '--:--' }}
                        </span>
                    </div>
                </div>

                {{-- Rechts: Gerät und Lautstärke --}}
                <div class="flex items-center justify-end gap-3">
                    <div class="relative">
                        <button type="button" wire:click="toggleDevices" @disabled(! $canControl)
                                title="Ausgabegerät wechseln"
                                @class([
                                    'flex max-w-44 items-center gap-1.5 rounded-lg px-2 py-1 text-xs transition disabled:opacity-30',
                                    'bg-white/10 text-emerald-300' => $showDevices,
                                    'text-slate-500 hover:bg-white/5 hover:text-slate-300' => ! $showDevices,
                                ])>
                            <x-dash.icon name="device" class="size-4 shrink-0" />
                            <span class="truncate">{{ $track?->deviceName ?? 'Gerät wählen' }}</span>
                        </button>

                        @if ($showDevices)
                            <div class="absolute right-0 bottom-full z-20 mb-2 w-64 rounded-xl border border-white/10 bg-slate-900/95 p-1.5 shadow-2xl shadow-black/60 backdrop-blur-xl"
                                 data-devices>
                                <p class="px-2 py-1.5 text-[0.65rem] font-semibold tracking-widest text-slate-600 uppercase">
                                    Ausgabe auf
                                </p>

                                @forelse ($devices as $device)
                                    <button type="button"
                                            wire:click="transferTo('{{ $device['id'] }}')"
                                            wire:key="device-{{ $device['id'] }}"
                                            @disabled($device['restricted'])
                                            data-device="{{ $device['name'] }}"
                                            @class([
                                                'flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left text-sm transition',
                                                'text-emerald-300' => $device['active'],
                                                'text-slate-300 hover:bg-white/5' => ! $device['active'] && ! $device['restricted'],
                                                'cursor-not-allowed text-slate-600' => $device['restricted'],
                                            ])>
                                        <x-dash.icon :name="$device['icon']" class="size-4 shrink-0" />
                                        <span class="truncate">{{ $device['name'] }}</span>
                                        @if ($device['active'])
                                            <x-dash.icon name="check-small" class="ml-auto size-4 shrink-0" />
                                        @elseif ($device['restricted'])
                                            <span class="ml-auto shrink-0 text-[0.65rem]">gesperrt</span>
                                        @endif
                                    </button>
                                @empty
                                    <p class="px-2 py-3 text-xs text-slate-500">
                                        Kein Gerät gefunden. Öffne Spotify einmal auf dem gewünschten Gerät –
                                        ein Echo taucht erst auf, wenn dort schon einmal etwas lief.
                                    </p>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    @if ($track?->volumePercent !== null)
                        <div class="flex items-center gap-2">
                            <x-dash.icon name="volume" class="size-4 text-slate-500" />
                            <input type="range" min="0" max="100" step="5"
                                   value="{{ $track->volumePercent }}"
                                   @disabled(! $canControl)
                                   x-on:change="$wire.setVolume(Number($event.target.value))"
                                   class="h-1 w-24 cursor-pointer appearance-none rounded-full bg-white/10 accent-emerald-400 disabled:opacity-30">
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex items-center justify-center gap-4 text-xs">
                @if ($track?->url)
                    <a href="{{ $track->url }}" target="_blank" rel="noopener"
                       class="text-slate-600 underline underline-offset-4 hover:text-slate-400">In Spotify öffnen</a>
                @endif

                <form method="POST" action="{{ route('spotify.disconnect') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-slate-600 underline underline-offset-4 hover:text-slate-400">
                        Verbindung trennen
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
