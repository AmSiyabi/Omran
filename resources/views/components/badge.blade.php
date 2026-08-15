@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'bg-navy/5 text-navy',
        'gold' => 'bg-gold/15 text-gold-deep',
        'success' => 'bg-success-soft text-success',
        'error' => 'bg-error-soft text-error',
        'warning' => 'bg-warning-soft text-warning',
        'info' => 'bg-info-soft text-info',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium '.($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
