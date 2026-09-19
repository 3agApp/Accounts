@extends('layouts.app')

@section('title', 'Confirm password')

@section('content')
    <x-auth-card title="Confirm your password" description="This is a secure area. Please confirm your password before continuing.">
        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <x-input-label for="password">Password</x-input-label>
                <x-text-input id="password" name="password" type="password" required autofocus autocomplete="current-password" />
                <x-input-error for="password" />
            </div>

            <x-primary-button>Confirm</x-primary-button>
        </form>
    </x-auth-card>
@endsection
