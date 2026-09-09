<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Dauerbetrieb auf dem iPad: Vollbild ohne Safari-Leisten, wenn zum Homescreen hinzugefuegt --}}
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#020617">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">

        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts -->
        @fonts

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full overflow-hidden bg-slate-950 font-sans text-slate-100 antialiased selection:bg-sky-500/30">
        <div class="flex h-full">
            <livewire:layout.sidenav />

            <main class="relative flex-1 overflow-y-auto">
                {{-- Dezenter Farbverlauf im Hintergrund, damit die Kacheln nicht auf Schwarz schweben --}}
                <div class="pointer-events-none fixed inset-0 -z-10">
                    <div class="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-sky-500/10 blur-3xl"></div>
                    <div class="absolute -bottom-40 right-1/4 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl"></div>
                </div>

                <div class="flex min-h-full flex-col px-8 py-7">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
