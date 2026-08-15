@php
    use App\Enums\CohortStatus;

    $course = $cohort->course;
    $tz = config('app.display_timezone');
@endphp

<x-layouts.public
    :title="$cohort->displayTitle()"
    :description="$course->summary_ar"
    :canonical="route('public.cohorts.show', $cohort->code)"
>
    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        <header>
            <a href="{{ route('public.courses.show', $course->slug) }}" class="text-sm text-muted hover:text-navy">
                ← {{ __('public.back_to_course') }}
            </a>
            <h1 class="mt-3 text-4xl leading-tight text-navy">{{ $cohort->displayTitle() }}</h1>
            <p class="mt-3 max-w-2xl leading-relaxed text-muted">{{ $course->summary_ar }}</p>
        </header>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            {{-- التفاصيل --}}
            <div class="lg:col-span-2">
                <section class="rounded-xl border border-line bg-white p-6" aria-labelledby="details-title">
                    <h2 id="details-title" class="text-lg font-bold text-navy">{{ __('public.cohort_details') }}</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4 border-b border-line pb-3">
                            <dt class="text-muted">{{ __('public.cohort_dates') }}</dt>
                            <dd class="text-end font-medium text-navy">
                                {{ $cohort->starts_at->timezone($tz)->translatedFormat('j F Y') }}
                                – {{ $cohort->ends_at->timezone($tz)->translatedFormat('j F Y') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-line pb-3">
                            <dt class="text-muted">{{ __('public.cohort_mode') }}</dt>
                            <dd class="font-medium text-navy">{{ $cohort->delivery_mode->label() }}</dd>
                        </div>
                        @if ($cohort->venue_ar || $cohort->city_ar)
                            <div class="flex justify-between gap-4 border-b border-line pb-3">
                                <dt class="text-muted">{{ __('public.cohort_venue') }}</dt>
                                <dd class="text-end font-medium text-navy">{{ collect([$cohort->venue_ar, $cohort->city_ar])->filter()->implode(' — ') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">{{ __('public.cohort_price') }}</dt>
                            <dd class="font-bold text-navy">
                                {{ $cohort->is_free ? __('public.cohort_free') : \App\Support\Baisa::format($cohort->price_baisa) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- الجدول --}}
                @if ($cohort->sessions->isNotEmpty())
                    <section class="mt-6 rounded-xl border border-line bg-white p-6" data-reveal aria-labelledby="schedule-title">
                        <h2 id="schedule-title" class="text-lg font-bold text-navy">{{ __('public.cohort_schedule') }}</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach ($cohort->sessions as $session)
                                <li class="flex items-start gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy/5 text-sm font-bold text-navy">{{ $session->session_number }}</span>
                                    <div>
                                        <p class="font-medium text-navy">{{ $session->title_ar ?? __('courses.session') }}</p>
                                        <p class="mt-0.5 text-sm text-muted">
                                            {{ $session->starts_at->timezone($tz)->translatedFormat('j F Y — H:i') }}
                                            – {{ $session->ends_at->timezone($tz)->format('H:i') }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>

            {{-- الدعوة --}}
            <aside>
                <div class="rounded-xl bg-navy p-6 text-cream-warm lg:sticky lg:top-24">
                    @if ($cohort->status === CohortStatus::Open)
                        <p class="text-lg font-bold">{{ __('public.register_interest') }}</p>
                        @if ($cohort->capacity !== null && $cohort->capacity - $cohort->seats_taken <= 5)
                            <p class="mt-1 text-sm text-gold-light">{{ __('public.cohort_seats_left') }}</p>
                        @endif
                        <a href="{{ route('public.contact', ['subject' => $cohort->displayTitle().' — '.$cohort->code]) }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-gold px-6 font-bold text-navy-deep transition-colors hover:bg-gold-light">
                            {{ __('public.register_interest') }}
                        </a>
                    @elseif ($cohort->status === CohortStatus::Announced)
                        <p class="text-lg font-bold">{{ __('public.registration_open_soon') }}</p>
                        <a href="{{ route('public.contact', ['subject' => $cohort->displayTitle().' — '.$cohort->code]) }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-cream-warm/30 px-6 font-medium text-cream-warm transition-colors hover:bg-cream-warm/10">
                            {{ __('public.notify_me') }}
                        </a>
                    @elseif ($cohort->status === CohortStatus::Closed)
                        <p class="text-lg font-bold">{{ __('public.registration_closed') }}</p>
                    @else
                        <p class="text-lg font-bold">{{ __('public.cohort_delivered') }}</p>
                        <a href="{{ route('public.courses.show', $course->slug) }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-cream-warm/30 px-6 font-medium text-cream-warm transition-colors hover:bg-cream-warm/10">
                            {{ __('public.back_to_course') }}
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </article>
</x-layouts.public>
