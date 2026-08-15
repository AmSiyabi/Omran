@props(['course'])

@php
    $media = $course->getFirstMedia('cover');
    $accent = $course->category->accent_color ?? '#cda34f';
@endphp

<article {{ $attributes->merge(['class' => 'card-lift overflow-hidden rounded-xl border border-line bg-white']) }}>
    <a href="{{ route('public.courses.show', $course->slug) }}" class="block h-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold">
        <div class="relative aspect-[16/9] overflow-hidden bg-navy">
            @if ($media)
                <picture>
                    <source
                        type="image/avif"
                        srcset="{{ $media->getUrl('avif-480') }} 480w, {{ $media->getUrl('avif-960') }} 960w"
                        sizes="(min-width: 1024px) 400px, 100vw"
                    >
                    <source
                        type="image/webp"
                        srcset="{{ $media->getUrl('webp-480') }} 480w, {{ $media->getUrl('webp-960') }} 960w"
                        sizes="(min-width: 1024px) 400px, 100vw"
                    >
                    <img
                        src="{{ $media->getUrl('webp-480') }}"
                        alt=""
                        loading="lazy"
                        class="absolute inset-0 size-full object-cover"
                    >
                </picture>
            @else
                {{-- بديل من الهوية: النجمة الرباعية بخط رفيع على كحلي --}}
                <div class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                    <svg class="size-14 text-gold/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.75">
                        <path d="M12 1c.85 6 4.15 9.3 11 11-6.85 1.7-10.15 5-11 11-.85-6-4.15-9.3-11-11C7.85 10.3 11.15 7 12 1z" />
                    </svg>
                </div>
            @endif

            {{-- شريط لون التصنيف — rect بدل style= بسبب CSP --}}
            <svg class="absolute inset-x-0 top-0 h-1 w-full" preserveAspectRatio="none" aria-hidden="true"><rect width="100%" height="100%" fill="{{ $accent }}" /></svg>
        </div>

        <div class="p-5">
            <p class="flex items-center gap-2 text-xs text-muted">
                <svg class="size-2" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" fill="{{ $accent }}" /></svg>
                {{ $course->category->name_ar }}
            </p>

            <h3 class="mt-2 text-lg font-bold leading-snug text-navy">{{ $course->title_ar }}</h3>
            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-muted">{{ $course->summary_ar }}</p>

            <p class="mt-4 text-xs text-muted">
                {{ $course->level->label() }}
                · {{ rtrim(rtrim((string) $course->duration_hours, '0'), '.') }} {{ __('public.hours') }}
            </p>
        </div>
    </a>
</article>
