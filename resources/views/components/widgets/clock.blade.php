<x-dash.widget {{ $attributes }} accent="slate">
    <div class="flex h-full flex-col justify-center"
         x-data="{
             time: '--:--',
             seconds: '--',
             date: '',
             tick() {
                 const now = new Date();
                 this.time = now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
                 this.seconds = now.toLocaleTimeString('de-DE', { second: '2-digit' });
                 this.date = now.toLocaleDateString('de-DE', { weekday: 'long', day: 'numeric', month: 'long' });
             }
         }"
         x-init="tick(); setInterval(() => tick(), 1000)">
        <div class="flex items-baseline gap-1.5">
            <span class="text-6xl font-light tabular-nums tracking-tight text-white" x-text="time">--:--</span>
            <span class="text-xl font-light tabular-nums text-slate-500" x-text="seconds">--</span>
        </div>
        <p class="mt-2 text-sm text-slate-400" x-text="date">{{ now()->translatedFormat('l, j. F') }}</p>
    </div>
</x-dash.widget>
