<x-layouts.public
    :title="__('public.about_title')"
    :description="__('public.about_lead')"
    :canonical="route('public.about')"
>
    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        <header class="relative overflow-hidden">
            <svg class="pointer-events-none absolute -end-16 -top-10 size-64 text-navy/[0.04]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.25" aria-hidden="true">
                <path d="M12 1c.85 6 4.15 9.3 11 11-6.85 1.7-10.15 5-11 11-.85-6-4.15-9.3-11-11C7.85 10.3 11.15 7 12 1z" />
            </svg>
            <h1 class="text-4xl text-navy">{{ __('public.about_title') }}</h1>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <p class="relative mt-5 max-w-2xl text-lg leading-relaxed text-muted">{{ __('public.about_lead') }}</p>
        </header>

        <section class="mt-12" data-reveal aria-labelledby="story-title">
            <h2 id="story-title" class="text-2xl text-navy">{{ __('public.about_story_title') }}</h2>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <div class="mt-5 max-w-3xl space-y-4 leading-loose text-navy/90">
                <p>{{ __('public.about_story_p1') }}</p>
                <p>{{ __('public.about_story_p2') }}</p>
            </div>
        </section>

        <section class="mt-12" data-reveal aria-labelledby="values-title">
            <h2 id="values-title" class="text-2xl text-navy">{{ __('public.about_values_title') }}</h2>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>

            <div class="mt-6 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['title' => __('public.about_value_1_title'), 'text' => __('public.about_value_1_text')],
                    ['title' => __('public.about_value_2_title'), 'text' => __('public.about_value_2_text')],
                    ['title' => __('public.about_value_3_title'), 'text' => __('public.about_value_3_text')],
                ] as $value)
                    <div class="rounded-xl border border-line bg-white p-6">
                        <svg class="size-6 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                            <path d="M12 1c.85 6 4.15 9.3 11 11-6.85 1.7-10.15 5-11 11-.85-6-4.15-9.3-11-11C7.85 10.3 11.15 7 12 1z" />
                        </svg>
                        <h3 class="mt-3 text-lg font-bold text-navy">{{ $value['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $value['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($partners->isNotEmpty())
            <section class="mt-12" data-reveal aria-labelledby="partners-title">
                <h2 id="partners-title" class="text-2xl text-navy">{{ __('public.about_partners_title') }}</h2>
                <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>

                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    @foreach ($partners as $partner)
                        <article class="rounded-xl border border-line bg-white p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-navy">
                                    <svg class="size-7 text-gold" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-navy">{{ $partner->display_name_ar }}</h3>
                                    <p class="text-sm font-medium text-gold-deep">{{ __('public.founding_partner') }}</p>
                                </div>
                            </div>
                            <p class="mt-4 leading-relaxed text-muted">{{ $partner->bio_ar }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-14 rounded-2xl bg-navy p-8 text-center sm:p-10" data-reveal>
            <h2 class="text-2xl text-cream-warm">{{ __('public.contact_band_title') }}</h2>
            <a href="{{ route('public.contact') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-lg bg-gold px-8 font-bold text-navy-deep transition-colors hover:bg-gold-light">
                {{ __('public.contact_band_cta') }}
            </a>
        </section>
    </article>
</x-layouts.public>
