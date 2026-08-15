@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? ($name ? 'field-'.$name : 'field-'.uniqid());
    $error ??= $name && $errors->has($name) ? $errors->first($name) : null;

    // محتوى لاتيني الاتجاه (بريد، هاتف، أرقام) يُكتب من اليسار داخل واجهة يمينية
    $ltrContent = in_array($type, ['email', 'tel', 'url', 'number', 'password'], true);

    $classes = 'block w-full min-h-11 rounded-lg border bg-white px-3.5 text-navy placeholder:text-muted/70 transition focus:outline-none focus:ring-2 '.(
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

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($required) required @endif
        @if ($ltrContent) dir="ltr" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >

    @if ($hint && ! $error)
        <p class="text-sm text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-error">{{ $error }}</p>
    @endif
</div>
