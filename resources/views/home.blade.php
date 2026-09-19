@extends('layouts.app')

@section('title', 'Your 3AG apps')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-6 py-12">
        <h1 class="text-2xl font-semibold tracking-tight">Your 3AG apps</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
            Signed in as {{ $user->name }}. Choose an application to continue — you will not be asked to sign in again.
        </p>

        @if (count($apps) > 0)
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($apps as $app)
                    <a href="{{ $app['url'] }}"
                       class="group flex flex-col rounded-xl border border-neutral-200 bg-white p-6 shadow-sm transition hover:border-neutral-300 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700">
                        <h2 class="text-base font-semibold tracking-tight">{{ $app['name'] }}</h2>
                        <p class="mt-2 flex-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $app['description'] }}</p>
                        <span class="mt-4 text-sm font-medium text-neutral-900 transition group-hover:underline dark:text-neutral-100">
                            Open {{ $app['name'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="mt-8 rounded-xl border border-dashed border-neutral-300 p-10 text-center dark:border-neutral-700">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    No applications are configured yet. Set SALESREPORT_URL, PRODUCTSYNC_URL or COMPLIANCEPLATFORM_URL in your environment file.
                </p>
            </div>
        @endif
    </div>
@endsection
