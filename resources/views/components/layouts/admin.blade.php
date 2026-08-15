@props(['title' => null])

@php
    $navItems = [
        [
            'label' => __('common.nav_home'),
            'route' => 'admin.dashboard',
            'active' => request()->routeIs('admin.dashboard'),
            'can' => true,
            'icon' => 'M11.47 3.84a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.06l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 0 0 1.061 1.06l8.69-8.69z M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.43z',
        ],
        [
            'label' => __('common.nav_courses'),
            'route' => 'admin.courses',
            'active' => request()->routeIs('admin.courses*'),
            'can' => auth()->user()?->can('courses.view') ?? false,
            'icon' => 'M11.25 4.533A9.707 9.707 0 0 0 6 3a9.735 9.735 0 0 0-3.25.555.75.75 0 0 0-.5.707v14.25a.75.75 0 0 0 1 .707A8.237 8.237 0 0 1 6 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0 1 18 18.75c1.155 0 2.24.24 3.25.555a.75.75 0 0 0 1-.707V4.262a.75.75 0 0 0-.5-.707A9.735 9.735 0 0 0 18 3a9.707 9.707 0 0 0-5.25 1.533v16.103z',
        ],
        [
            'label' => __('common.nav_finance'),
            'route' => 'admin.finance',
            'active' => request()->routeIs('admin.finance*'),
            'can' => auth()->user()?->can('finance.view') ?? false,
            'icon' => 'M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3',
        ],
        [
            'label' => __('common.nav_more'),
            'route' => 'admin.more',
            'active' => request()->routeIs('admin.more', 'admin.security*'),
            'can' => true,
            'icon' => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z',
        ],
    ];
    $visibleItems = array_values(array_filter($navItems, fn ($item) => $item['can']));
@endphp

<x-layouts.base :title="$title">
    <div class="min-h-dvh">
        {{-- الشريط الجانبي — سطح المكتب --}}
        <aside class="fixed inset-y-0 start-0 z-40 hidden w-64 flex-col bg-navy text-cream-warm lg:flex">
            <div class="flex items-center justify-center border-b border-cream-warm/10 px-6 py-6">
                <a href="{{ route('admin.dashboard') }}" wire:navigate>
                    <img src="/images/brand/logo-cream-transparent.png" alt="{{ __('common.center_short') }}" class="h-12 w-auto">
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="{{ __('common.main_navigation') }}">
                @foreach ($visibleItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors',
                            'bg-cream-warm/10 text-gold-light' => $item['active'],
                            'text-cream-warm/80 hover:bg-cream-warm/5 hover:text-cream-warm' => ! $item['active'],
                        ])
                        @if ($item['active']) aria-current="page" @endif
                    >
                        <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="{{ $item['icon'] }}" clip-rule="evenodd" />
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-cream-warm/10 p-4">
                <p class="truncate text-sm font-medium">{{ auth()->user()?->name_ar }}</p>
                <p class="truncate text-xs text-cream-warm/60">{{ auth()->user()?->email }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 text-sm text-cream-warm/80 transition-colors hover:bg-cream-warm/5 hover:text-cream-warm">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        {{ __('auth.logout') }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- ترويسة الجوال --}}
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-line bg-cream/90 px-4 py-3 backdrop-blur lg:hidden">
            <a href="{{ route('admin.dashboard') }}" wire:navigate>
                <img src="/images/brand/logo-navy-transparent.png" alt="{{ __('common.center_short') }}" class="h-9 w-auto">
            </a>
            <span class="max-w-[50%] truncate text-sm font-medium text-navy">{{ auth()->user()?->name_ar }}</span>
        </header>

        {{-- المحتوى --}}
        <main class="px-4 py-6 pb-28 sm:px-6 lg:ms-64 lg:pb-10">
            {{ $slot }}
        </main>

        {{-- شريط التنقل السفلي — الجوال --}}
        {{-- inline style= is blocked by our CSP — column count via class variants --}}
        <nav
            @class([
                'fixed inset-x-0 bottom-0 z-40 grid border-t border-line bg-white pb-[env(safe-area-inset-bottom)] lg:hidden',
                'grid-cols-1' => count($visibleItems) === 1,
                'grid-cols-2' => count($visibleItems) === 2,
                'grid-cols-3' => count($visibleItems) === 3,
                'grid-cols-4' => count($visibleItems) === 4,
            ])
            aria-label="{{ __('common.main_navigation') }}"
        >
            @foreach ($visibleItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    @class([
                        'flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs font-medium',
                        'text-gold-deep' => $item['active'],
                        'text-muted' => ! $item['active'],
                    ])
                    @if ($item['active']) aria-current="page" @endif
                >
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="{{ $item['icon'] }}" clip-rule="evenodd" />
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</x-layouts.base>
