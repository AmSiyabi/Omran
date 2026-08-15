@props([
    'variant' => 'line',
])

@php
    $variants = [
        'line' => 'h-4 w-full rounded',
        'circle' => 'size-10 rounded-full',
        'block' => 'h-24 w-full rounded-lg',
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'animate-pulse bg-navy/10 '.($variants[$variant] ?? $variants['line'])]) }}
    aria-hidden="true"
></div>
