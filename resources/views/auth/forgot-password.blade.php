@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
    <x-auth-card title="Forgot your password?" description="Enter your email address and we will send you a reset link.">
        <x-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-input-label for="email">Email address</x-input-label>
                <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <x-input-error for="email" />
            </div>

            <x-primary-button>Email password reset link</x-primary-button>
        </form>

        <x-slot:footer>
            <a href="{{ route('login') }}" class="font-medium text-neutral-900 underline-offset-4 hover:underline dark:text-neutral-100">Back to sign in</a>
        </x-slot:footer>
    </x-auth-card>
@endsection
