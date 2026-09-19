@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <x-auth-card title="Create your account" description="One account for every application in the 3AG suite.">
        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-input-label for="name">Name</x-input-label>
                <x-text-input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
                <x-input-error for="name" />
            </div>

            <div>
                <x-input-label for="email">Email address</x-input-label>
                <x-text-input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" />
                <x-input-error for="email" />
            </div>

            <div>
                <x-input-label for="password">Password</x-input-label>
                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                <x-input-error for="password" />
            </div>

            <div>
                <x-input-label for="password_confirmation">Confirm password</x-input-label>
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                <x-input-error for="password_confirmation" />
            </div>

            <x-primary-button>Create account</x-primary-button>
        </form>

        <x-slot:footer>
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-neutral-900 underline-offset-4 hover:underline dark:text-neutral-100">Sign in</a>
        </x-slot:footer>
    </x-auth-card>
@endsection
