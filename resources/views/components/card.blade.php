@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-white shadow-sm']) }}>
    @isset($header)
        <div class="border-b border-line px-5 py-4">
            {{ $header }}
        </div>
    @endisset

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-line bg-cream/60 px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
