@props(['variant' => 'primary', 'type' => 'submit'])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-100 focus-visible:outline-slate-500',
        default => 'border border-transparent bg-slate-900 text-white hover:bg-slate-700 focus-visible:outline-slate-900',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 {$classes}"]) }}
>
    {{ $slot }}
</button>
