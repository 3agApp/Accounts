@props(['type' => 'text'])

<input
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'mt-2 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:focus:border-neutral-300 dark:focus:ring-neutral-300']) }}
>
