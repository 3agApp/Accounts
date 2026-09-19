@props([
    'title',
    'description' => null,
])

<div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-12">
    <div class="rounded-xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <h1 class="text-xl font-semibold tracking-tight">{{ $title }}</h1>

        @if ($description)
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ $description }}</p>
        @endif

        <div class="mt-8">
            {{ $slot }}
        </div>
    </div>

    @isset($footer)
        <p class="mt-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
            {{ $footer }}
        </p>
    @endisset
</div>
