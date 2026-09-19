<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? '' }}{{ isset($title) ? ' — ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 text-lg font-semibold tracking-tight">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-900 text-sm font-bold text-white">3</span>
            {{ config('app.name') }}
        </a>

        <main class="w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm">
            {{ $slot }}
        </main>

        @isset($below)
            <div class="mt-6 w-full max-w-sm">{{ $below }}</div>
        @endisset
    </div>
</body>
</html>
