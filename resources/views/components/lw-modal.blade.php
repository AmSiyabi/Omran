{{--
    نافذة يتحكم بها Livewire مباشرة (خاصية show + اسم إجراء الإغلاق).
    تُستخدم داخل مكونات Livewire حيث تكون الحالة على الخادم.
--}}
@props([
    'show' => false,
    'close' => null,
    'title' => null,
    'maxWidth' => 'md',
])

@php
    $widths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];
    $width = $widths[$maxWidth] ?? $widths['md'];
@endphp

@if ($show)
    <div
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-label="{{ $title }}" @endif
        @if ($close) wire:keydown.escape.window="{{ $close }}" @endif
    >
        @if ($close)
            <button type="button" class="fixed inset-0 cursor-default bg-navy-deep/60" wire:click="{{ $close }}" aria-label="{{ __('common.close') }}" tabindex="-1"></button>
        @else
            <div class="fixed inset-0 bg-navy-deep/60" aria-hidden="true"></div>
        @endif

        <div class="relative max-h-[92dvh] w-full {{ $width }} overflow-y-auto rounded-t-2xl bg-cream p-6 shadow-xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4">
                @if ($title)
                    <h2 class="text-lg font-bold text-navy">{{ $title }}</h2>
                @endif

                @if ($close)
                    <button
                        type="button"
                        class="-m-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted transition hover:bg-navy/5 hover:text-navy"
                        wire:click="{{ $close }}"
                        aria-label="{{ __('common.close') }}"
                    >
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                @endif
            </div>

            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
