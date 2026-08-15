<x-layouts.public
    :title="__('public.instructors_title')"
    :description="__('public.instructors_lead')"
    :canonical="route('public.instructors')"
>
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6" aria-labelledby="instructors-title">
        <div class="max-w-2xl">
            <h1 id="instructors-title" class="text-4xl text-navy">{{ __('public.instructors_title') }}</h1>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <p class="mt-4 text-muted">{{ __('public.instructors_lead') }}</p>
        </div>

        @if ($partners->isEmpty() && $instructors->isEmpty())
            <p class="mt-12 text-muted">{{ __('public.no_instructors_yet') }}</p>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($partners as $partner)
                    <article class="card-lift rounded-xl border border-line bg-white p-6 text-center" data-reveal>
                        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-navy">
                            <svg class="size-8 text-gold" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-navy">{{ $partner->display_name_ar }}</h2>
                        <p class="mt-1 text-sm font-medium text-gold-deep">{{ __('public.founding_partner') }}</p>
                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted">{{ $partner->bio_ar }}</p>
                    </article>
                @endforeach

                @foreach ($instructors as $instructor)
                    <article class="card-lift rounded-xl border border-line bg-white p-6 text-center" data-reveal>
                        <a href="{{ route('public.instructors.show', $instructor->id) }}" class="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold">
                            <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-cream">
                                <svg class="size-9 text-navy/40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438zM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="mt-4 text-lg font-bold text-navy">{{ $instructor->name_ar }}</h2>
                            <p class="mt-1 text-sm font-medium text-muted">{{ $instructor->specialization_ar ?? __('public.external_instructor') }}</p>
                            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted">{{ $instructor->bio_ar }}</p>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
