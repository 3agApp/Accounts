<x-layouts.app title="Your account">
    <h1 class="text-xl font-semibold tracking-tight">Your products</h1>
    <p class="mt-1 text-sm text-zinc-500">
        One sign-in covers everything listed here. Signing in to a product sends you back here if your session has expired.
    </p>

    <div class="mt-6 space-y-3">
        @forelse ($clients as $client)
            @php($homeUrl = $client->homeUrl())

            <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4">
                <div>
                    <p class="font-medium">{{ $client->name }}</p>
                    @if ($homeUrl)
                        <p class="mt-0.5 text-xs text-zinc-500">{{ preg_replace('#^https?://#', '', $homeUrl) }}</p>
                    @endif
                </div>

                @if ($homeUrl)
                    <a href="{{ $homeUrl }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
                        Open
                    </a>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-8 text-center">
                <p class="text-sm text-zinc-500">No products have been enabled for this account yet.</p>
            </div>
        @endforelse
    </div>
</x-layouts.app>
