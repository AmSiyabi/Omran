<x-layouts.public
    :title="__('public.courses_title')"
    :description="__('public.courses_lead')"
    :canonical="route('public.courses')"
>
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6" aria-labelledby="courses-title">
        <div class="max-w-2xl">
            <h1 id="courses-title" class="text-4xl text-navy">{{ __('public.courses_title') }}</h1>
            <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
            <p class="mt-4 text-muted">{{ __('public.courses_lead') }}</p>
        </div>

        {{-- تصفية بالتصنيف — روابط خادمية صديقة لمحركات البحث --}}
        <nav class="scrollbar-none -mx-4 mt-8 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ __('courses.categories') }}">
            <a
                href="{{ route('public.courses') }}"
                @class([
                    'inline-flex min-h-10 shrink-0 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                    'border-navy bg-navy text-cream-warm' => $currentCategory === null,
                    'border-line bg-white text-navy hover:bg-navy/5' => $currentCategory !== null,
                ])
            >
                {{ __('public.catalog_all') }}
            </a>
            @foreach ($categories as $category)
                <a
                    href="{{ route('public.courses', ['category' => $category->slug]) }}"
                    @class([
                        'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors',
                        'border-navy bg-navy text-cream-warm' => $currentCategory?->id === $category->id,
                        'border-line bg-white text-navy hover:bg-navy/5' => $currentCategory?->id !== $category->id,
                    ])
                >
                    <svg class="size-2" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" fill="{{ $category->accent_color ?? '#cda34f' }}" /></svg>
                    {{ $category->name_ar }}
                </a>
            @endforeach
        </nav>

        @if ($courses->isEmpty())
            <div class="mt-16 text-center text-muted">
                <svg class="mx-auto size-12 text-gold/50" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
                </svg>
                <p class="mt-4">{{ __('public.no_courses_in_category') }}</p>
            </div>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-site.course-card :course="$course" data-reveal />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $courses->links() }}
            </div>
        @endif
    </section>
</x-layouts.public>
