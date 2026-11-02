@props(['type' => 'info'])

@php
    $classes = match ($type) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-rose-200 bg-rose-50 text-rose-900',
        default => 'border-sky-200 bg-sky-50 text-sky-900',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$classes}"]) }} role="status">
    {{ $slot }}
</div>
