@extends('layouts.app')

@section('title', 'Verify email')

@section('content')
    <x-auth-card title="Verify your email address" description="We sent a verification link to your inbox. Open it to finish setting up your account.">
        <x-session-status :status="session('status') === 'verification-link-sent' ? 'A new verification link has been sent to your email address.' : null" />

        <div class="flex flex-col gap-4">
            @if (Route::has('verification.send'))
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-primary-button>Resend verification email</x-primary-button>
                </form>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-secondary-button>Sign out</x-secondary-button>
            </form>
        </div>
    </x-auth-card>
@endsection
