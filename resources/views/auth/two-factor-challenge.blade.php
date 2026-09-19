@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('content')
    <x-auth-card title="Two-factor authentication" description="Enter the code from your authenticator app to finish signing in.">
        <form method="POST" action="{{ route('two-factor.login') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-input-label for="code">Authentication code</x-input-label>
                <x-text-input id="code" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" />
                <x-input-error for="code" />
            </div>

            <details class="group" @error('recovery_code') open @enderror>
                <summary class="cursor-pointer list-none text-sm text-neutral-500 underline-offset-4 transition hover:text-neutral-900 hover:underline dark:text-neutral-400 dark:hover:text-neutral-100">
                    Lost your device? Use a recovery code
                </summary>

                <div class="mt-4">
                    <x-input-label for="recovery_code">Recovery code</x-input-label>
                    <x-text-input id="recovery_code" name="recovery_code" autocomplete="one-time-code" />
                    <x-input-error for="recovery_code" />
                </div>
            </details>

            <x-primary-button>Continue</x-primary-button>
        </form>
    </x-auth-card>
@endsection
