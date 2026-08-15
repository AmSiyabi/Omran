@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'target' => null,
    'loadingLabel' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium select-none transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold disabled:opacity-60 disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-navy text-cream-warm hover:bg-navy-ink active:bg-navy-deep',
        'gold' => 'bg-gold text-navy-deep hover:bg-gold-light active:bg-gold-deep',
        'secondary' => 'border border-navy/25 text-navy hover:bg-navy/5 active:bg-navy/10',
        'ghost' => 'text-navy hover:bg-navy/5 active:bg-navy/10',
        'danger' => 'bg-error text-white hover:bg-error/90 active:bg-error',
    ];

    // min-h-11 = 44px: الحد الأدنى لهدف اللمس (spec §12.1)
    $sizes = [
        'sm' => 'min-h-9 px-3 text-sm',
        'md' => 'min-h-11 px-5 text-base',
        'lg' => 'min-h-12 px-6 text-lg',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
    $loadingLabel ??= __('common.loading');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@elseif ($target)
    <button
        type="{{ $type }}"
        wire:loading.attr="disabled"
        wire:target="{{ $target }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <span wire:loading.remove wire:target="{{ $target }}" class="inline-flex items-center gap-2">{{ $slot }}</span>
        <span wire:loading.flex wire:target="{{ $target }}" class="items-center gap-2">
            <x-spinner class="size-4" />
            {{ $loadingLabel }}
        </span>
    </button>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
