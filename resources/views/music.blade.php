<x-dashboard-layout>
    <div class="flex min-h-0 flex-1 flex-col">
        <header class="mb-2 flex items-baseline justify-between">
            <h1 class="text-xl font-medium tracking-tight text-white">Musik</h1>
            <p class="text-xs text-slate-600">Spotify</p>
        </header>

        @if (session('spotify-status'))
            <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('spotify-status') }}
            </div>
        @endif

        @if (session('spotify-error'))
            <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                {{ session('spotify-error') }}
            </div>
        @endif

        <livewire:widgets.music variant="page" />
    </div>
</x-dashboard-layout>
