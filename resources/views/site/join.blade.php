@php
    use App\Support\Baisa;

    $course = $cohort->course;
    $tz = config('app.display_timezone');
    $price = $cohort->is_free ? 0 : ($link->price_override_baisa ?? $cohort->price_baisa);
    $seatsFull = $cohort->capacity !== null && $cohort->seats_taken >= $cohort->capacity;
@endphp

<x-layouts.public
    :title="__('courses.join_title').' — '.$cohort->displayTitle()"
    :description="$course->summary_ar"
>
    <x-slot:head>
        <meta name="robots" content="noindex">
    </x-slot:head>

    <section class="mx-auto max-w-2xl px-4 py-14 sm:px-6">
        {{-- ملخص الدفعة --}}
        <div class="rounded-xl border border-line bg-white p-6">
            <p class="flex items-center gap-2 text-xs text-muted">
                <svg class="size-2" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" fill="{{ $course->category->accent_color ?? '#cda34f' }}" /></svg>
                {{ $course->category->name_ar }}
                @if ($link->label_ar)
                    · {{ $link->label_ar }}
                @endif
            </p>
            <h1 class="mt-2 text-3xl text-navy">{{ $cohort->displayTitle() }}</h1>
            <p class="mt-2 text-sm text-muted">
                {{ $cohort->starts_at->timezone($tz)->translatedFormat('j F Y') }}
                – {{ $cohort->ends_at->timezone($tz)->translatedFormat('j F Y') }}
                · {{ $cohort->delivery_mode->label() }}
                @if ($cohort->city_ar) · {{ $cohort->city_ar }} @endif
            </p>
            <p class="mt-3 font-bold text-navy">
                {{ __('courses.join_price') }}:
                {{ $price === 0 ? __('courses.join_free') : Baisa::format($price) }}
            </p>
        </div>

        @if ($joinedStatus)
            {{-- حالة النجاح --}}
            @php
                $successKey = match ($joinedStatus) {
                    'confirmed' => 'confirmed',
                    'waitlisted' => 'waitlisted',
                    default => 'pending',
                };
            @endphp
            <div class="mt-6 rounded-xl border border-success/30 bg-success-soft p-6 text-center" role="status">
                <svg class="mx-auto size-10 text-success" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
                <h2 class="mt-3 text-xl font-bold text-navy">{{ __('courses.join_success_'.$successKey.'_title') }}</h2>
                <p class="mt-2 text-muted">{{ __('courses.join_success_'.$successKey.'_text') }}</p>
            </div>
        @elseif ($unusableReason !== null)
            {{-- الرابط غير صالح — رسالة ودية، لا صفحة خطأ --}}
            <div class="mt-6 rounded-xl border border-warning/30 bg-warning-soft p-6 text-center">
                <svg class="mx-auto size-10 text-warning" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <h2 class="mt-3 text-xl font-bold text-navy">{{ __('courses.join_unusable_'.$unusableReason) }}</h2>
                <a href="{{ route('public.contact') }}" class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-navy/25 px-5 text-sm font-medium text-navy transition-colors hover:bg-navy/5">
                    {{ __('courses.join_contact_us') }}
                </a>
            </div>
        @else
            {{-- النموذج --}}
            <div class="mt-6">
                <h2 class="text-xl font-bold text-navy">{{ __('courses.join_title') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('courses.join_lead') }}</p>

                @if ($seatsFull && ! $link->requires_approval)
                    <p class="mt-3 rounded-lg bg-warning-soft p-3 text-sm text-warning">{{ __('courses.join_seats_full_note') }}</p>
                @endif
                @if ($link->requires_approval)
                    <p class="mt-3 rounded-lg bg-info-soft p-3 text-sm text-info">{{ __('courses.join_requires_approval_note') }}</p>
                @endif

                <form method="POST" action="{{ route('public.join.store', $link->token) }}" class="mt-5 space-y-5">
                    @csrf

                    <x-input :label="__('courses.join_full_name')" name="full_name_ar" :value="old('full_name_ar')" required autocomplete="name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-input :label="__('courses.join_email')" name="email" type="email" :value="old('email')" required autocomplete="email" />
                        <x-input :label="__('courses.join_phone')" name="phone" type="tel" :value="old('phone')" required autocomplete="tel" inputmode="tel" />
                        <x-input :label="__('courses.join_organization')" name="organization_ar" :value="old('organization_ar')" autocomplete="organization" />
                        <x-input :label="__('courses.join_job_title')" name="job_title_ar" :value="old('job_title_ar')" autocomplete="organization-title" />
                    </div>

                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-gold px-8 text-base font-bold text-navy-deep transition-colors hover:bg-gold-light">
                        {{ __('courses.join_submit') }}
                    </button>
                </form>
            </div>
        @endif
    </section>
</x-layouts.public>
