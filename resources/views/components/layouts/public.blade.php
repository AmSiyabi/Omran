@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
])

@php
    $pageTitle = $title ? $title.' — '.__('common.center_short') : __('public.default_title');
    $pageDescription = $description ?? __('public.default_description');
    $pageImage = $image ?? url('/images/og-default.png');
    $pageCanonical = $canonical ?? url()->current();

    $navItems = [
        ['route' => 'public.home', 'label' => __('public.nav_home'), 'active' => request()->routeIs('public.home')],
        ['route' => 'public.courses', 'label' => __('public.nav_courses'), 'active' => request()->routeIs('public.courses*')],
        ['route' => 'public.work', 'label' => __('public.nav_work'), 'active' => request()->routeIs('public.work')],
        ['route' => 'public.instructors', 'label' => __('public.nav_instructors'), 'active' => request()->routeIs('public.instructors*')],
        ['route' => 'public.about', 'label' => __('public.nav_about'), 'active' => request()->routeIs('public.about')],
    ];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16202f">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageCanonical }}">

    <meta property="og:site_name" content="{{ __('common.center_name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:locale" content="ar">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="/images/brand/star-gold-transparent.png">

    <link rel="preload" href="/fonts/el-messiri-var-arabic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/tajawal-400-arabic.woff2" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/public.js'])

    {{-- يُفعّل حالة الإخفاء المسبق للكشف عند التمرير فقط عند توفر JS --}}
    <script nonce="{{ Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.classList.add('js')</script>

    {{ $head ?? '' }}
</head>
<body class="min-h-dvh bg-cream text-navy antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:start-2 focus:z-[100] focus:rounded-lg focus:bg-navy focus:px-4 focus:py-2 focus:text-cream-warm">
        {{ __('public.skip_to_content') }}
    </a>

    <header class="sticky top-0 z-50 border-b border-line bg-cream/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('public.home') }}" class="shrink-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold" aria-label="{{ __('common.center_name') }}">
                <img src="/images/brand/logo-navy-sm.webp" alt="{{ __('common.center_short') }}" class="h-10 w-auto" width="120" height="40">
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('common.main_navigation') }}">
                @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'min-h-11 inline-flex items-center rounded-lg px-3 text-sm font-medium transition-colors',
                            'text-navy' => $item['active'],
                            'text-muted hover:text-navy' => ! $item['active'],
                        ])
                        @if ($item['active']) aria-current="page" @endif
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <a href="{{ route('public.contact') }}" class="ms-2 inline-flex min-h-11 items-center rounded-lg border border-navy/25 px-4 text-sm font-medium text-navy transition-colors hover:bg-navy hover:text-cream-warm">
                    {{ __('public.nav_contact') }}
                </a>
            </nav>

            <button
                type="button"
                class="flex size-11 items-center justify-center rounded-lg text-navy lg:hidden"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="mobile-nav"
                aria-label="{{ __('common.main_navigation') }}"
            >
                <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>

        <nav id="mobile-nav" class="hidden border-t border-line px-4 pb-4 lg:hidden" data-nav-menu aria-label="{{ __('common.main_navigation') }}">
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'flex min-h-12 items-center border-b border-line/60 text-base',
                        'font-bold text-navy' => $item['active'],
                        'text-navy/80' => ! $item['active'],
                    ])
                    @if ($item['active']) aria-current="page" @endif
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('public.contact') }}" class="flex min-h-12 items-center text-base font-medium text-gold-deep">
                {{ __('public.nav_contact') }}
            </a>
        </nav>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="mt-20 bg-navy text-cream-warm">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <div class="grid gap-10 sm:grid-cols-3">
                <div>
                    <img src="/images/brand/logo-cream-sm.webp" alt="{{ __('common.center_short') }}" class="h-12 w-auto" width="144" height="48" loading="lazy">
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-cream-warm/70">
                        {{ __('public.footer_about') }}
                    </p>
                </div>

                <nav aria-label="{{ __('public.footer_links') }}">
                    <h2 class="text-sm font-bold text-gold-light">{{ __('public.footer_links') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($navItems as $item)
                            <li><a href="{{ route($item['route']) }}" class="text-cream-warm/80 transition-colors hover:text-cream-warm">{{ $item['label'] }}</a></li>
                        @endforeach
                        <li><a href="{{ route('public.contact') }}" class="text-cream-warm/80 transition-colors hover:text-cream-warm">{{ __('public.nav_contact') }}</a></li>
                    </ul>
                </nav>

                <div>
                    <h2 class="text-sm font-bold text-gold-light">{{ __('public.nav_contact') }}</h2>
                    <p class="mt-4 text-sm text-cream-warm/80">{{ __('public.footer_location') }}</p>
                    <a href="{{ route('public.contact') }}" class="mt-3 inline-flex min-h-11 items-center rounded-lg border border-cream-warm/25 px-4 text-sm text-cream-warm transition-colors hover:bg-cream-warm/10">
                        {{ __('public.contact_cta_short') }}
                    </a>
                </div>
            </div>

            <div class="mt-10 flex items-center gap-4 border-t border-cream-warm/10 pt-6">
                <svg class="size-4 shrink-0 text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                </svg>
                <p class="flex-1 text-xs text-cream-warm/60">© {{ now()->timezone(config('app.display_timezone'))->year }} {{ __('common.center_name') }}</p>
                {{-- مدخل الإدارة أسفل الطية (spec §2.1) --}}
                <a href="{{ route('login') }}" class="text-xs text-cream-warm/40 transition-colors hover:text-cream-warm/80">{{ __('public.admin_login') }}</a>
            </div>
        </div>
    </footer>
</body>
</html>
