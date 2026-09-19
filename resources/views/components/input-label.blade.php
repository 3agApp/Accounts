@props(['for'])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm font-medium text-neutral-700 dark:text-neutral-300']) }}>
    {{ $slot }}
</label>
