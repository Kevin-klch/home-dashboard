<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#020617">

        <title>{{ config('app.name') }}</title>

        <link rel="manifest" href="/site.webmanifest">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">

        <!-- Fonts -->
        @fonts

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-950 font-sans text-slate-100 antialiased">
        <div class="relative flex min-h-full flex-col items-center justify-center px-6 py-10">
            {{-- Derselbe dezente Farbverlauf wie auf dem Dashboard --}}
            <div class="pointer-events-none fixed inset-0 -z-10">
                <div class="absolute -top-40 left-1/4 h-96 w-96 rounded-full bg-sky-500/10 blur-3xl"></div>
                <div class="absolute -bottom-40 right-1/4 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl"></div>
            </div>

            <div class="mb-8 flex flex-col items-center gap-3">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 shadow-lg shadow-sky-500/20">
                    <x-dash.icon name="home" class="size-7 text-white" />
                </span>

                <p class="text-lg font-medium text-white">{{ config('app.name') }}</p>
            </div>

            <div class="w-full max-w-xs">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
