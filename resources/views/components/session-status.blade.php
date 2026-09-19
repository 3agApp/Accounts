@props(['status' => null])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200']) }}>
        {{ $status }}
    </div>
@endif
