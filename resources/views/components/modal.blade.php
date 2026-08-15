@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
])

@php
    $widths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
    ];
    $width = $widths[$maxWidth] ?? $widths['md'];
@endphp

{{--
    يُفتح بـ: $dispatch('open-modal', 'اسم-النافذة') أو window.dispatchEvent
    يُغلق بـ: $dispatch('close-modal', 'اسم-النافذة') أو زر الإغلاق أو Escape
--}}
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
            role="dialog"
            aria-modal="true"
            @if ($title) aria-label="{{ $title }}" @endif
        >
            <div
                x-show="open"
                x-transition.opacity.duration.150ms
                class="fixed inset-0 bg-navy-deep/60"
                x-on:click="open = false"
                aria-hidden="true"
            ></div>

            <div
                x-show="open"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="relative w-full {{ $width }} rounded-t-2xl bg-cream p-6 shadow-xl sm:rounded-2xl"
            >
                <div class="flex items-start justify-between gap-4">
                    @if ($title)
                        <h2 class="text-lg font-bold text-navy">{{ $title }}</h2>
                    @endif

                    <button
                        type="button"
                        class="-m-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted transition hover:bg-navy/5 hover:text-navy focus-visible:outline-2 focus-visible:outline-gold"
                        x-on:click="open = false"
                        aria-label="{{ __('common.close') }}"
                    >
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-start">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </template>
</div>
