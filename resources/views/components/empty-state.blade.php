@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    @isset($icon)
        {{ $icon }}
    @else
        {{-- النجمة الرباعية — من هوية عمران (نقطة الميم في الشعار) --}}
        <svg class="size-12 text-gold/50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
        </svg>
    @endisset

    <h3 class="mt-4 text-lg font-bold text-navy">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
