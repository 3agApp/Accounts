@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <x-auth-card title="Sign in" description="Use your 3AG Accounts credentials to continue.">
        <x-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-input-label for="email">Email address</x-input-label>
                <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <x-input-error for="email" />
            </div>

            <div>
                <div class="flex items-center justify-between gap-4">
                    <x-input-label for="password">Password</x-input-label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-neutral-500 underline-offset-4 transition hover:text-neutral-900 hover:underline dark:text-neutral-400 dark:hover:text-neutral-100">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <x-text-input id="password" name="password" type="password" required autocomplete="current-password" />
                <x-input-error for="password" />
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                <input type="checkbox" name="remember" class="size-4 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-950">
                Remember me
            </label>

            <x-primary-button>Sign in</x-primary-button>
        </form>

        <x-slot:footer>
            @if (Route::has('register'))
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-neutral-900 underline-offset-4 hover:underline dark:text-neutral-100">Create one</a>
            @endif
        </x-slot:footer>
    </x-auth-card>
@endsection
