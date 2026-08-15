@php
    $media = $course->getFirstMedia('cover');
    $accent = $course->category->accent_color ?? '#cda34f';
    $ogImage = $media?->getUrl('webp-960');
@endphp

<x-layouts.public
    :title="$course->meta_title_ar ?? $course->title_ar"
    :description="$course->meta_description_ar ?? $course->summary_ar"
    :image="$ogImage"
    :canonical="route('public.courses.show', $course->slug)"
>
    <x-slot:head>
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Course",
            "name": "{{ $course->title_ar }}",
            "description": "{{ $course->summary_ar }}",
            "inLanguage": "ar",
            "provider": {
                "@@type": "EducationalOrganization",
                "name": "{{ __('common.center_name') }}",
                "url": "{{ route('public.home') }}"
            }@if ($upcomingCohorts->isNotEmpty()),
            "hasCourseInstance": [
                @foreach ($upcomingCohorts as $cohort)
                {
                    "@@type": "CourseInstance",
                    "courseMode": "{{ $cohort->delivery_mode->value === 'online' ? 'Online' : ($cohort->delivery_mode->value === 'hybrid' ? 'Blended' : 'Onsite') }}",
                    "startDate": "{{ $cohort->starts_at->toDateString() }}",
                    "endDate": "{{ $cohort->ends_at->toDateString() }}"
                }@if (! $loop->last),@endif
                @endforeach
            ]@endif
        }
        </script>
    </x-slot:head>

    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        {{-- الترويسة --}}
        <header>
            <p class="flex items-center gap-2 text-sm text-muted">
                <a href="{{ route('public.courses', ['category' => $course->category->slug]) }}" class="flex items-center gap-2 hover:text-navy">
                    <svg class="size-2" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" fill="{{ $accent }}" /></svg>
                    {{ $course->category->name_ar }}
                </a>
            </p>
            <h1 class="mt-3 text-4xl leading-tight text-navy">{{ $course->title_ar }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-muted">{{ $course->summary_ar }}</p>

            <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                <div class="flex gap-2">
                    <dt class="text-muted">{{ __('public.course_level') }}:</dt>
                    <dd class="font-medium text-navy">{{ $course->level->label() }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-muted">{{ __('public.course_duration') }}:</dt>
                    <dd class="font-medium text-navy">{{ rtrim(rtrim((string) $course->duration_hours, '0'), '.') }} {{ __('public.hours') }}</dd>
                </div>
            </dl>
        </header>

        @if ($media)
            <div class="relative mt-8 aspect-[21/9] overflow-hidden rounded-2xl bg-navy">
                <picture>
                    <source type="image/avif" srcset="{{ $media->getUrl('avif-960') }} 960w, {{ $media->getUrl('avif-1440') }} 1440w" sizes="(min-width: 1024px) 896px, 100vw">
                    <source type="image/webp" srcset="{{ $media->getUrl('webp-960') }} 960w, {{ $media->getUrl('webp-1440') }} 1440w" sizes="(min-width: 1024px) 896px, 100vw">
                    <img src="{{ $media->getUrl('webp-960') }}" alt="{{ $course->title_ar }}" class="absolute inset-0 size-full object-cover" fetchpriority="high">
                </picture>
            </div>
        @endif

        {{-- الوصف --}}
        <section class="prose-none mt-10 max-w-none" data-reveal>
            <p class="whitespace-pre-line leading-loose text-navy/90">{{ $course->description_ar }}</p>
        </section>

        {{-- المخرجات --}}
        @if ($course->outcomes_ar !== [])
            <section class="mt-10" data-reveal aria-labelledby="outcomes-title">
                <h2 id="outcomes-title" class="text-2xl text-navy">{{ __('public.course_outcomes') }}</h2>
                <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
                <ul class="mt-5 space-y-3">
                    @foreach ($course->outcomes_ar as $outcome)
                        <li class="flex items-start gap-3">
                            <svg class="mt-1 size-4 shrink-0 text-gold" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                            </svg>
                            <span class="leading-relaxed text-navy/90">{{ $outcome }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- الفئة المستهدفة والمتطلبات --}}
        <div class="mt-10 grid gap-8 sm:grid-cols-2">
            @if ($course->target_audience_ar)
                <section class="rounded-xl border border-line bg-white p-6" data-reveal aria-labelledby="audience-title">
                    <h2 id="audience-title" class="text-lg font-bold text-navy">{{ __('public.course_audience') }}</h2>
                    <p class="mt-2 leading-relaxed text-muted">{{ $course->target_audience_ar }}</p>
                </section>
            @endif
            @if ($course->prerequisites_ar)
                <section class="rounded-xl border border-line bg-white p-6" data-reveal aria-labelledby="prereq-title">
                    <h2 id="prereq-title" class="text-lg font-bold text-navy">{{ __('public.course_prerequisites') }}</h2>
                    <p class="mt-2 leading-relaxed text-muted">{{ $course->prerequisites_ar }}</p>
                </section>
            @endif
        </div>

        {{-- الدفعات القادمة --}}
        <section class="mt-12" data-reveal aria-labelledby="cohorts-title">
            <h2 id="cohorts-title" class="text-2xl text-navy">{{ __('public.upcoming_cohorts') }}</h2>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>

            @if ($upcomingCohorts->isEmpty())
                <div class="mt-5 rounded-xl border border-line bg-white p-6">
                    <p class="text-muted">{{ __('public.no_upcoming_cohorts') }}</p>
                    <a href="{{ route('public.contact', ['subject' => $course->title_ar]) }}" class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-navy/25 px-5 text-sm font-medium text-navy transition-colors hover:bg-navy/5">
                        {{ __('public.notify_me') }}
                    </a>
                </div>
            @else
                <ul class="mt-5 space-y-4">
                    @foreach ($upcomingCohorts as $cohort)
                        <li class="card-lift rounded-xl border border-line bg-white p-5">
                            <a href="{{ route('public.cohorts.show', $cohort->code) }}" class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="font-bold text-navy">
                                        {{ $cohort->starts_at->timezone(config('app.display_timezone'))->translatedFormat('j F Y') }}
                                        · {{ $cohort->delivery_mode->label() }}
                                    </p>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $cohort->city_ar ?? $cohort->venue_ar ?? '' }}
                                    </p>
                                </div>
                                <span class="inline-flex min-h-11 items-center rounded-lg bg-gold px-5 text-sm font-bold text-navy-deep">
                                    {{ $cohort->is_free ? __('public.cohort_free') : \App\Support\Baisa::format($cohort->price_baisa) }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </article>
</x-layouts.public>
