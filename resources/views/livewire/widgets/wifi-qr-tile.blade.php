<x-dash.widget :class="$class" title="WLAN" icon="wifi" accent="rose">
    @if ($svg === null)
        <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
            <x-dash.icon name="wifi" class="size-8 text-slate-700" />
            <p class="text-sm text-slate-400">WLAN noch nicht hinterlegt</p>
            <p class="text-xs text-slate-600">
                <code class="text-slate-500">WIFI_SSID</code> und
                <code class="text-slate-500">WIFI_PASSWORD</code> eintragen
            </p>
        </div>
    @else
        <div class="flex h-full items-center gap-5">
            {{--
                Weiße Fläche unter dem Code: dunkle Module auf hellem Grund sind
                die Kombination, die Kameras zuverlässig lesen.
            --}}
            <div class="shrink-0 rounded-xl bg-white p-2" data-qr>
                <div class="size-28 [&>svg]:size-full">
                    {!! $svg !!}
                </div>
            </div>

            <div class="min-w-0">
                <p class="text-xs tracking-widest text-slate-500 uppercase">Netzwerk</p>
                <p class="truncate text-lg font-medium text-white">{{ $network->ssid }}</p>

                <p class="mt-3 text-xs text-slate-400">Mit der Kamera scannen zum Verbinden</p>

                <p class="mt-1.5 text-[0.7rem] text-slate-600">
                    {{ $network->encryption->label() }}@if ($network->hidden) · verstecktes Netz @endif
                </p>
            </div>
        </div>
    @endif
</x-dash.widget>
