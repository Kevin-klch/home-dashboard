@php
    // ACHTUNG: Rein dekoratives Muster, KEIN scanbarer QR-Code.
    // Fuer einen echten Code spaeter z. B. simplesoftwareio/simple-qrcode einbinden.
    $modules = 21;
    $finderZones = [[0, 0], [$modules - 7, 0], [0, $modules - 7]];

    $isFinderArea = function (int $x, int $y) use ($finderZones): bool {
        foreach ($finderZones as [$fx, $fy]) {
            if ($x >= $fx - 1 && $x <= $fx + 7 && $y >= $fy - 1 && $y <= $fy + 7) {
                return true;
            }
        }
        return false;
    };

    $cells = [];
    for ($y = 0; $y < $modules; $y++) {
        for ($x = 0; $x < $modules; $x++) {
            if (! $isFinderArea($x, $y) && crc32("home-dashboard-{$x}-{$y}") % 100 < 47) {
                $cells[] = [$x, $y];
            }
        }
    }
@endphp

<x-dash.widget {{ $attributes }} title="WLAN" icon="wifi" accent="rose">
    <div class="flex h-full items-center gap-5">
        <div class="shrink-0 rounded-xl bg-white p-2.5">
            <svg viewBox="-0.5 -0.5 {{ $modules + 1 }} {{ $modules + 1 }}" class="size-28" role="img"
                 aria-label="Platzhalter für einen WLAN-QR-Code">
                @foreach ($cells as [$x, $y])
                    <rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" rx="0.2" fill="#0f172a" />
                @endforeach

                @foreach ($finderZones as [$fx, $fy])
                    <rect x="{{ $fx + 0.5 }}" y="{{ $fy + 0.5 }}" width="6" height="6" rx="1.4"
                          fill="none" stroke="#0f172a" stroke-width="1" />
                    <rect x="{{ $fx + 2 }}" y="{{ $fy + 2 }}" width="3" height="3" rx="0.7" fill="#0f172a" />
                @endforeach
            </svg>
        </div>

        <div class="min-w-0">
            <p class="text-xs tracking-widest text-slate-500 uppercase">Netzwerk</p>
            <p class="truncate text-lg font-medium text-white">Zuhause-WLAN</p>
            <p class="mt-3 text-xs text-slate-400">Zum Verbinden mit der Kamera scannen</p>
            <p class="mt-1.5 inline-flex rounded-md bg-rose-500/15 px-2 py-0.5 text-[0.7rem] text-rose-300">
                Platzhalter, noch nicht scanbar
            </p>
        </div>
    </div>
</x-dash.widget>
