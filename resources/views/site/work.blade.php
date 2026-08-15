<x-layouts.public
    :title="__('public.work_title')"
    :description="__('public.work_lead')"
    :canonical="route('public.work')"
>
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6" aria-labelledby="work-title">
        <div class="max-w-2xl">
            <h1 id="work-title" class="text-4xl text-navy">{{ __('public.work_title') }}</h1>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <p class="mt-4 text-muted">{{ __('public.work_lead') }}</p>
        </div>

        {{-- الأرقام --}}
        <dl class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-4" data-reveal>
            @foreach ([
                ['value' => $stats['courses'], 'label' => __('public.work_stat_courses')],
                ['value' => $stats['cohorts'], 'label' => __('public.work_stat_cohorts')],
                ['value' => $stats['hours'], 'label' => __('public.work_stat_hours')],
                ['value' => $stats['clients'], 'label' => __('public.work_stat_clients')],
            ] as $stat)
                <div class="rounded-xl border border-line bg-white p-6 text-center">
                    <dd class="text-3xl font-bold text-navy">{{ $stat['value'] }}</dd>
                    <dt class="mt-1 text-sm text-muted">{{ $stat['label'] }}</dt>
                </div>
            @endforeach
        </dl>

        {{-- برامج منفذة --}}
        <section class="mt-14" data-reveal aria-labelledby="delivered-title">
            <h2 id="delivered-title" class="text-2xl text-navy">{{ __('public.work_delivered_title') }}</h2>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>

            @if ($deliveredCohorts->isEmpty())
                <p class="mt-6 text-muted">{{ __('public.work_empty') }}</p>
            @else
                <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($deliveredCohorts as $cohort)
                        <li class="rounded-xl border border-line bg-white p-5">
                            <p class="flex items-center gap-2 text-xs text-muted">
                                <svg class="size-2" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" fill="{{ $cohort->course->category->accent_color ?? '#cda34f' }}" /></svg>
                                {{ $cohort->course->category->name_ar }}
                                · {{ $cohort->ends_at->timezone(config('app.display_timezone'))->translatedFormat('F Y') }}
                            </p>
                            <h3 class="mt-2 font-bold text-navy">{{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</h3>
                            @if ($cohort->client)
                                <p class="mt-1 text-sm text-muted">{{ $cohort->client->name_ar }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- عملاؤنا --}}
        @if ($clients->isNotEmpty())
            <section class="mt-14" data-reveal aria-labelledby="clients-title">
                <h2 id="clients-title" class="text-2xl text-navy">{{ __('public.work_clients_title') }}</h2>
                <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>

                <ul class="mt-6 flex flex-wrap gap-3">
                    @foreach ($clients as $client)
                        <li class="inline-flex min-h-11 items-center rounded-full border border-line bg-white px-5 text-sm font-medium text-navy">
                            {{ $client->name_ar }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </section>
</x-layouts.public>
