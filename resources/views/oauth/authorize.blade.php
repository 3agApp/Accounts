<x-layouts.guest title="Authorize {{ $client->name }}">
    <h1 class="text-lg font-semibold tracking-tight">Authorization request</h1>
    <p class="mt-1 text-sm text-zinc-500">
        <strong class="font-medium text-zinc-900">{{ $client->name }}</strong> wants to access your
        {{ config('app.name') }} account.
    </p>

    @if (count($scopes) > 0)
        <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">This will let it</p>
            <ul class="mt-2 space-y-1.5 text-sm text-zinc-700">
                @foreach ($scopes as $scope)
                    <li class="flex items-start gap-2">
                        <span aria-hidden="true" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                        {{ $scope->description }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mt-4 text-xs text-zinc-500">Signed in as {{ $user->email }}.</p>

    <div class="mt-6 flex gap-3">
        <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
            @csrf
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-primary-button class="w-full">Allow</x-primary-button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
            @csrf
            @method('DELETE')
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                Deny
            </button>
        </form>
    </div>
</x-layouts.guest>
