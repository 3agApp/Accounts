<x-layouts.guest title="Choose a new password">
    <h1 class="text-lg font-semibold tracking-tight">Choose a new password</h1>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-text-input label="Email" name="email" type="email" :value="old('email', $request->email)" required autocomplete="username" />
        <x-text-input label="New password" name="password" type="password" required autofocus autocomplete="new-password" />
        <x-text-input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />

        <x-primary-button class="w-full">Save new password</x-primary-button>
    </form>
</x-layouts.guest>
