<x-layouts.guest title="Confirm your password">
    <h1 class="text-lg font-semibold tracking-tight">Confirm your password</h1>
    <p class="mt-1 text-sm text-zinc-500">This is a secure area. Please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <x-text-input label="Password" name="password" type="password" required autofocus autocomplete="current-password" />

        <x-primary-button class="w-full">Confirm</x-primary-button>
    </form>
</x-layouts.guest>
