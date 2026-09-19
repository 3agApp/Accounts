<button {{ $attributes->merge(['type' => 'submit', 'class' => 'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
