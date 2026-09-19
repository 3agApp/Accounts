<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? '' }}{{ isset($title) ? ' — ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 font-sans text-zinc-900 antialiased">
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-900 text-sm font-bold text-white">3</span>
                {{ config('app.name') }}
            </a>

            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('settings') }}" class="text-zinc-600 hover:text-zinc-900">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-zinc-600 hover:text-zinc-900">Sign out</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
