@props([
    'title',
    'subtitle' => null,
    'actions' => null,
])

<header {{ $attributes->class(['mb-6 flex items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-medium tracking-tight text-white">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endif
</header>
