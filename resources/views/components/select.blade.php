@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id') ?? ($name ? 'field-'.$name : 'field-'.uniqid());
    $error ??= $name && $errors->has($name) ? $errors->first($name) : null;

    $classes = 'block w-full min-h-11 appearance-none rounded-lg border bg-white ps-3.5 pe-10 text-navy transition focus:outline-none focus:ring-2 '.(
        $error
            ? 'border-error focus:border-error focus:ring-error/25'
            : 'border-line focus:border-gold focus:ring-gold/30'
    );
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-navy">
            {{ $label }}
            @if ($required)
                <span class="text-error" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($required) required @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->merge(['class' => $classes]) }}
        >
            @if ($placeholder)
                <option value="" disabled selected>{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>

        {{-- سهم لأسفل — لا يُعكس في RTL لأنه لا يدل على اتجاه --}}
        <svg class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z" clip-rule="evenodd" />
        </svg>
    </div>

    @if ($hint && ! $error)
        <p class="text-sm text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-error">{{ $error }}</p>
    @endif
</div>
