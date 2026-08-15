<x-layouts.public
    :title="$instructor->name_ar"
    :description="$instructor->specialization_ar"
    :canonical="route('public.instructors.show', $instructor->id)"
>
    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <a href="{{ route('public.instructors') }}" class="text-sm text-muted hover:text-navy">
            ← {{ __('public.instructors_title') }}
        </a>

        <header class="mt-6 flex items-center gap-6">
            <div class="flex size-24 shrink-0 items-center justify-center rounded-full bg-navy">
                <svg class="size-10 text-gold" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl text-navy">{{ $instructor->name_ar }}</h1>
                @if ($instructor->specialization_ar)
                    <p class="mt-1 font-medium text-gold-deep">{{ $instructor->specialization_ar }}</p>
                @endif
            </div>
        </header>

        @if ($instructor->bio_ar)
            <p class="mt-8 whitespace-pre-line leading-loose text-navy/90">{{ $instructor->bio_ar }}</p>
        @endif
    </article>
</x-layouts.public>
