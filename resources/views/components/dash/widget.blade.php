@props([
    'title' => null,
    'icon' => null,
    'accent' => 'sky',
    'action' => null,
])

@php
    // Vollstaendige Klassennamen, damit der Tailwind-Scanner sie findet.
    $accents = [
        'sky'     => 'bg-sky-500/15 text-sky-300',
        'amber'   => 'bg-amber-500/15 text-amber-300',
        'emerald' => 'bg-emerald-500/15 text-emerald-300',
        'violet'  => 'bg-violet-500/15 text-violet-300',
        'rose'    => 'bg-rose-500/15 text-rose-300',
        'slate'   => 'bg-slate-500/15 text-slate-300',
    ];
    $accentClasses = $accents[$accent] ?? $accents['sky'];
@endphp

<section {{ $attributes->class([
    'flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5',
    'shadow-lg shadow-black/20 backdrop-blur-xl',
]) }}>
    @if ($title)
        <header class="mb-4 flex items-center gap-3">
            @if ($icon)
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $accentClasses }}">
                    <x-dash.icon :name="$icon" class="size-5" />
                </span>
            @endif

            <h2 class="text-sm font-medium tracking-wide text-slate-300">{{ $title }}</h2>

            @if ($action)
                <div class="ml-auto">{{ $action }}</div>
            @endif
        </header>
    @endif

    <div class="flex min-h-0 flex-1 flex-col">
        {{ $slot }}
    </div>
</section>
