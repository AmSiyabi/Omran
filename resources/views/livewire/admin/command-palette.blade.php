{{--
    لوحة الأوامر — Ctrl+K / Cmd+K على سطح المكتب.
    Alpine يدير الفتح والتنقل بالأسهم؛ Livewire يجلب النتائج.
--}}
<div
    x-data="commandPalette($wire)"
    x-on:keydown.ctrl.k.window.prevent="show()"
    x-on:keydown.meta.k.window.prevent="show()"
    x-on:open-palette.window="show()"
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[60] flex items-start justify-center px-4 pt-[12dvh]"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('common.palette_title') }}"
            x-on:keydown.escape.stop="hide()"
            x-on:keydown.down.prevent="move(1)"
            x-on:keydown.up.prevent="move(-1)"
            x-on:keydown.enter.prevent="go()"
            x-on:keydown.tab.prevent="$refs.q.focus()"
        >
            <div class="fixed inset-0 bg-navy-deep/60" x-on:click="hide()" aria-hidden="true"></div>

            <div
                x-show="open"
                x-transition:enter="transition duration-150 ease-out"
                x-transition:enter-start="-translate-y-2 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div class="flex items-center gap-3 border-b border-line px-4">
                    <svg class="size-5 shrink-0 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11zM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9z" clip-rule="evenodd" />
                    </svg>
                    <input
                        type="text"
                        x-ref="q"
                        data-palette-input
                        wire:model.live.debounce.150ms="query"
                        class="min-h-12 w-full border-0 bg-transparent text-navy placeholder:text-muted focus:outline-none focus:ring-0"
                        placeholder="{{ __('common.palette_placeholder') }}"
                        aria-label="{{ __('common.palette_title') }}"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <kbd class="hidden shrink-0 rounded border border-line px-1.5 py-0.5 text-[10px] text-muted sm:block">Esc</kbd>
                </div>

                <div class="max-h-[50dvh] overflow-y-auto p-2" x-ref="list">
                    @php $lastGroup = null; @endphp
                    @forelse ($this->results as $index => $result)
                        @if ($result['group'] !== $lastGroup)
                            <p class="px-3 pb-1 pt-3 text-xs font-medium text-muted first:pt-1">{{ $result['group'] }}</p>
                            @php $lastGroup = $result['group']; @endphp
                        @endif
                        <a
                            href="{{ $result['url'] }}"
                            wire:navigate
                            wire:key="palette-{{ $index }}-{{ md5($result['url']) }}"
                            data-palette-item
                            x-on:click="hide()"
                            x-on:mousemove="highlightElement($el)"
                            class="flex min-h-11 items-center justify-between gap-3 rounded-lg px-3 text-sm text-navy"
                        >
                            <span class="truncate">{{ $result['label'] }}</span>
                            @if ($result['hint'] !== null)
                                <span class="shrink-0 text-xs text-muted">{{ $result['hint'] }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-muted">{{ __('common.no_results') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </template>
</div>
