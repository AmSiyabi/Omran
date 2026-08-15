<x-layouts.public>
    <x-slot:head>
        <link rel="preload" as="image" href="/images/brand/logo-navy-sm.webp" fetchpriority="high">
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "EducationalOrganization",
            "name": "{{ __('common.center_name') }}",
            "alternateName": "Omran Center for Training and Consulting",
            "url": "{{ route('public.home') }}",
            "logo": "{{ url('/images/brand/logo-navy-sm.webp') }}",
            "areaServed": "OM",
            "description": "{{ __('public.default_description') }}"
        }
        </script>
    </x-slot:head>

    {{-- ═══ البطل — الأطروحة، لا اللافتة (spec §5.2) ═══ --}}
    <section class="relative overflow-hidden border-b border-line">
        {{-- النجمة كعلامة مائية بخط رفيع — الزخرفة الوحيدة --}}
        <svg class="pointer-events-none absolute -start-24 top-1/2 size-[28rem] -translate-y-1/2 text-navy/[0.04] sm:size-[36rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.25" aria-hidden="true">
            <path d="M12 1c.85 6 4.15 9.3 11 11-6.85 1.7-10.15 5-11 11-.85-6-4.15-9.3-11-11C7.85 10.3 11.15 7 12 1z" />
        </svg>

        <div class="relative mx-auto max-w-6xl px-4 py-20 text-center sm:px-6 sm:py-28 lg:py-32">
            {{-- 1: الشعار يستقر --}}
            <img
                src="/images/brand/logo-navy-sm.webp"
                alt="{{ __('common.center_short') }}"
                class="hero-seq hero-seq-1 mx-auto h-24 w-auto sm:h-32"
                width="288"
                height="128"
                fetchpriority="high"
            >

            {{-- 2: سطر الأطروحة --}}
            <h1 class="hero-seq hero-seq-2 mx-auto mt-8 max-w-3xl text-4xl leading-tight text-navy sm:text-5xl lg:text-6xl">
                {{ __('public.hero_thesis') }}
            </h1>

            {{-- 3: الشرح --}}
            <p class="hero-seq hero-seq-3 mx-auto mt-6 max-w-2xl text-base leading-relaxed text-muted sm:text-lg">
                {{ __('public.hero_sub') }}
            </p>

            {{-- 4: الدعوة — الذهب علامة ترقيم واحدة في الشاشة --}}
            <div class="hero-seq hero-seq-4 mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('public.courses') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-gold px-8 text-base font-bold text-navy-deep transition-colors hover:bg-gold-light sm:w-auto">
                    {{ __('public.hero_cta') }}
                </a>
                <a href="{{ route('public.about') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-navy/25 px-8 text-base font-medium text-navy transition-colors hover:bg-navy/5 sm:w-auto">
                    {{ __('public.hero_secondary_cta') }}
                </a>
            </div>
        </div>
    </section>

    {{-- ═══ الكتالوج هو المنتج (spec §5.2) ═══ --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" aria-labelledby="catalog-title">
        <div class="max-w-2xl" data-reveal>
            <h2 id="catalog-title" class="text-3xl text-navy">{{ __('public.catalog_title') }}</h2>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <p class="mt-4 text-muted">{{ __('public.catalog_lead') }}</p>
        </div>

        @if ($courses->isEmpty())
            <p class="mt-10 text-muted" data-reveal>{{ __('public.no_courses_yet') }}</p>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-site.course-card :course="$course" data-reveal />
                @endforeach
            </div>

            <div class="mt-10 text-center" data-reveal>
                <a href="{{ route('public.courses') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-navy/25 px-6 font-medium text-navy transition-colors hover:bg-navy/5">
                    {{ __('public.catalog_all') }}
                </a>
            </div>
        @endif
    </section>

    {{-- ═══ ماذا نقدم ═══ --}}
    <section class="border-y border-line bg-white" aria-labelledby="services-title">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="max-w-2xl" data-reveal>
                <h2 id="services-title" class="text-3xl text-navy">{{ __('public.services_title') }}</h2>
                <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            </div>

            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['title' => __('public.service_training_title'), 'text' => __('public.service_training_text')],
                    ['title' => __('public.service_consulting_title'), 'text' => __('public.service_consulting_text')],
                    ['title' => __('public.service_talks_title'), 'text' => __('public.service_talks_text')],
                ] as $service)
                    <div data-reveal>
                        <svg class="size-7 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                            <path d="M12 1c.85 6 4.15 9.3 11 11-6.85 1.7-10.15 5-11 11-.85-6-4.15-9.3-11-11C7.85 10.3 11.15 7 12 1z" />
                        </svg>
                        <h3 class="mt-4 text-xl font-bold text-navy">{{ $service['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-muted">{{ $service['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ عن المركز — مقتطف ═══ --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" aria-labelledby="about-teaser-title">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div data-reveal>
                <h2 id="about-teaser-title" class="text-3xl text-navy">{{ __('public.about_teaser_title') }}</h2>
                <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
                <p class="mt-4 max-w-xl leading-relaxed text-muted">{{ __('public.about_teaser_text') }}</p>
                <a href="{{ route('public.about') }}" class="mt-6 inline-flex min-h-11 items-center rounded-lg border border-navy/25 px-6 font-medium text-navy transition-colors hover:bg-navy/5">
                    {{ __('public.about_teaser_cta') }}
                </a>
            </div>

            <div class="flex items-center justify-center rounded-2xl bg-navy p-14" data-reveal>
                <img src="/images/brand/logo-gold-sm.webp" alt="" class="h-28 w-auto sm:h-36" loading="lazy" width="324" height="144">
            </div>
        </div>
    </section>

    {{-- ═══ نداء التواصل ═══ --}}
    <section class="bg-navy" aria-labelledby="contact-band-title">
        <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6" data-reveal>
            <h2 id="contact-band-title" class="text-3xl text-cream-warm">{{ __('public.contact_band_title') }}</h2>
            <p class="mx-auto mt-3 max-w-xl text-cream-warm/70">{{ __('public.contact_band_text') }}</p>
            <a href="{{ route('public.contact') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-lg bg-gold px-8 text-base font-bold text-navy-deep transition-colors hover:bg-gold-light">
                {{ __('public.contact_band_cta') }}
            </a>
        </div>
    </section>
</x-layouts.public>
