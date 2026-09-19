@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
    <x-auth-card title="Choose a new password" description="Set a new password for your 3AG Accounts login.">
        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email">Email address</x-input-label>
                <x-text-input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
                <x-input-error for="email" />
            </div>

            <div>
                <x-input-label for="password">New password</x-input-label>
                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                <x-input-error for="password" />
            </div>

            <div>
                <x-input-label for="password_confirmation">Confirm new password</x-input-label>
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                <x-input-error for="password_confirmation" />
            </div>

            <x-primary-button>Reset password</x-primary-button>
        </form>
    </x-auth-card>
@endsection
