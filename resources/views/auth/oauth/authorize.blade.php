@extends('layouts.app')

@section('title', 'Authorization request')

@section('content')
    <x-auth-card title="Authorization request">
        <p class="-mt-6 mb-8 text-sm text-neutral-500 dark:text-neutral-400">
            <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $client->name }}</span>
            is requesting permission to access your 3AG Accounts profile.
        </p>

        @if (count($scopes) > 0)
            <div class="mb-8">
                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">This will allow it to:</p>

                <ul class="mt-3 flex flex-col gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                    @foreach ($scopes as $scope)
                        <li class="flex gap-2">
                            <span aria-hidden="true" class="mt-2 size-1.5 shrink-0 rounded-full bg-neutral-400 dark:bg-neutral-600"></span>
                            <span>{{ $scope->description }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('passport.authorizations.approve').($request->nonce ? '?nonce='.urlencode($request->nonce) : '') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <x-primary-button>Approve</x-primary-button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <x-secondary-button>Deny</x-secondary-button>
            </form>
        </div>

        <x-slot:footer>
            Signed in as {{ $user->email }}.
        </x-slot:footer>
    </x-auth-card>
@endsection
