<x-layouts.app title="Settings">
    <h1 class="text-xl font-semibold tracking-tight">Settings</h1>

    <div class="mt-6 space-y-6">
        <section class="rounded-xl border border-zinc-200 bg-white p-6">
            <h2 class="font-medium">Profile</h2>
            <p class="mt-0.5 text-sm text-zinc-500">Your name and email as the products see them.</p>

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <x-text-input label="Name" name="name" :value="old('name', $user->name)" required />
                <x-text-input label="Email" name="email" type="email" :value="old('email', $user->email)" required />

                <div class="flex justify-end">
                    <x-primary-button>Save</x-primary-button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6">
            <h2 class="font-medium">Password</h2>
            <p class="mt-0.5 text-sm text-zinc-500">Changing this changes your sign-in for every product.</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <x-text-input label="Current password" name="current_password" type="password" required autocomplete="current-password" />
                <x-text-input label="New password" name="password" type="password" required autocomplete="new-password" />
                <x-text-input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />

                <div class="flex justify-end">
                    <x-primary-button>Update password</x-primary-button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6">
            <h2 class="font-medium">Two-factor authentication</h2>

            @if ($user->hasEnabledTwoFactorAuthentication())
                <p class="mt-0.5 text-sm text-emerald-700">Enabled. You are asked for a code every time you sign in.</p>

                <div class="mt-5">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>
                </div>

                <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-5 flex justify-end">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                        Turn off
                    </button>
                </form>
            @else
                <p class="mt-0.5 text-sm text-zinc-500">Add a second step to every sign-in, across every product.</p>

                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-5 flex justify-end">
                    @csrf
                    <x-primary-button>Turn on</x-primary-button>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
