<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') &middot; @endif{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
    <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-sm font-semibold tracking-tight">
                <span class="flex size-8 items-center justify-center rounded-md bg-neutral-900 text-xs font-bold text-white dark:bg-white dark:text-neutral-900">3AG</span>
                <span>{{ config('app.name') }}</span>
            </a>

            @auth
                <div class="flex items-center gap-4">
                    <span class="hidden text-sm text-neutral-500 sm:inline dark:text-neutral-400">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md px-3 py-1.5 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100">
                            Sign out
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="flex flex-1 flex-col">
        @yield('content')
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="mx-auto w-full max-w-5xl px-6 text-xs text-neutral-500 dark:text-neutral-400">
            {{ config('app.name') }} &mdash; single sign-on for the 3AG suite.
        </p>
    </footer>
</body>
</html>
