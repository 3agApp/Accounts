<x-layouts.guest title="Two-factor authentication">
    <h1 class="text-lg font-semibold tracking-tight">Two-factor authentication</h1>
    <p class="mt-1 text-sm text-zinc-500">Enter the code from your authenticator app.</p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-text-input label="Authentication code" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" />

        <x-primary-button class="w-full">Continue</x-primary-button>
    </form>

    <details class="mt-6">
        <summary class="cursor-pointer text-sm text-zinc-600 hover:text-zinc-900">Use a recovery code instead</summary>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4 space-y-4">
            @csrf

            <x-text-input label="Recovery code" name="recovery_code" autocomplete="one-time-code" />

            <x-primary-button class="w-full">Continue</x-primary-button>
        </form>
    </details>
</x-layouts.guest>
