@props(['current'])

@php
    $items = [
        ['key' => 'courses', 'route' => 'admin.courses', 'label' => __('courses.courses')],
        ['key' => 'cohorts', 'route' => 'admin.cohorts', 'label' => __('courses.cohorts')],
        ['key' => 'categories', 'route' => 'admin.categories', 'label' => __('courses.categories')],
        ['key' => 'instructors', 'route' => 'admin.instructors', 'label' => __('courses.instructors')],
        ['key' => 'clients', 'route' => 'admin.clients', 'label' => __('courses.clients')],
    ];
@endphp

<nav class="scrollbar-none -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('courses.courses') }}">
    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            wire:navigate
            @class([
                'inline-flex min-h-9 shrink-0 items-center rounded-full px-4 text-sm font-medium transition-colors',
                'bg-navy text-cream-warm' => $current === $item['key'],
                'bg-white text-navy border border-line hover:bg-navy/5' => $current !== $item['key'],
            ])
            @if ($current === $item['key']) aria-current="page" @endif
        >
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
