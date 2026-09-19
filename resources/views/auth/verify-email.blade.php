<x-layouts.guest title="Verify your email">
    <h1 class="text-lg font-semibold tracking-tight">Verify your email</h1>
    <p class="mt-1 text-sm text-zinc-500">
        We sent a verification link to your inbox. Open it to finish setting up your account.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-5 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            A fresh verification link is on its way.
        </div>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">Resend verification email</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-sm text-zinc-600 underline hover:text-zinc-900">Sign out</button>
        </form>
    </div>
</x-layouts.guest>
