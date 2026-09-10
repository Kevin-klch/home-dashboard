<div>
    @if ($svg === null)
        <div class="rounded-2xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur-xl">
            <x-dash.icon name="wifi" class="mx-auto size-10 text-slate-700" />
            <p class="mt-4 text-lg text-slate-200">WLAN noch nicht hinterlegt</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                <code class="text-slate-400">WIFI_SSID</code> und
                <code class="text-slate-400">WIFI_PASSWORD</code> in die
                <code class="text-slate-400">.env</code> eintragen. Für ein offenes Netz
                <code class="text-slate-400">WIFI_ENCRYPTION=none</code>.
            </p>
        </div>
    @else
        <div class="flex flex-col items-center rounded-2xl border border-white/10 bg-white/5 p-10 text-center backdrop-blur-xl">
            {{-- Dunkle Module auf hellem Grund: so lesen Kameras zuverlässig --}}
            <div class="rounded-2xl bg-white p-4" data-qr>
                <div class="size-64 [&>svg]:size-full">
                    {!! $svg !!}
                </div>
            </div>

            <p class="mt-8 text-xs tracking-widest text-slate-500 uppercase">Netzwerk</p>
            <p class="mt-1 text-2xl font-medium text-white">{{ $network->ssid }}</p>

            <p class="mt-6 max-w-sm text-sm text-slate-400">
                Mit der Kamera scannen – Handy und Tablet verbinden sich damit ohne Passworteingabe.
            </p>

            <p class="mt-2 text-xs text-slate-600">
                {{ $network->encryption->label() }}@if ($network->hidden) · verstecktes Netz @endif
            </p>
        </div>
    @endif
</div>
