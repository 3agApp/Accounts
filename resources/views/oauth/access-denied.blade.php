<x-layouts.guest title="No access">
    <h1 class="text-lg font-semibold tracking-tight">You don't have access to {{ $client->name }}</h1>
    <p class="mt-2 text-sm text-zinc-500">
        Your {{ config('app.name') }} sign-in worked, but this account has not been granted access to
        {{ $client->name }}. Ask your account manager to enable it.
    </p>

    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="block w-full rounded-lg bg-zinc-900 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-zinc-800">
            Back to your account
        </a>
    </div>
</x-layouts.guest>
