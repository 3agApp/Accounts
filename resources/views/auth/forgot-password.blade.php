<x-layouts.guest title="Reset your password">
    <h1 class="text-lg font-semibold tracking-tight">Reset your password</h1>
    <p class="mt-1 text-sm text-zinc-500">We will email you a link to choose a new one.</p>

    @if (session('status'))
        <div class="mt-5 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <x-text-input label="Email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />

        <x-primary-button class="w-full">Email password reset link</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-zinc-600 underline hover:text-zinc-900">Back to sign in</a>
    </p>
</x-layouts.guest>
