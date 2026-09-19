<x-layouts.guest title="Sign in">
    <h1 class="text-lg font-semibold tracking-tight">Sign in</h1>
    <p class="mt-1 text-sm text-zinc-500">Use your 3AG Accounts credentials.</p>

    @if (session('status'))
        <div class="mt-5 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-text-input label="Email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-text-input label="Password" name="password" type="password" required autocomplete="current-password" />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-zinc-600">
                <input type="checkbox" name="remember" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-zinc-600 underline hover:text-zinc-900">Forgot password?</a>
        </div>

        <x-primary-button class="w-full">Sign in</x-primary-button>
    </form>

    <p class="mt-6 text-center text-xs text-zinc-500">
        Accounts are created by an administrator. Contact your account manager if you need access.
    </p>
</x-layouts.guest>
