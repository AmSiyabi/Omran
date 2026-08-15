@props(['title' => null])

<x-layouts.base :title="$title">
    <main class="flex min-h-dvh flex-col items-center justify-center px-4 py-10">
        <a href="/" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">
            <img src="/images/brand/logo-navy-transparent.png" alt="{{ __('common.center_short') }}" class="h-16 w-auto">
        </a>

        <div class="mt-8 w-full max-w-md">
            <x-card>
                @if ($title)
                    <h1 class="mb-5 text-center text-xl text-navy">{{ $title }}</h1>
                @endif

                {{ $slot }}
            </x-card>
        </div>

        <p class="mt-6 text-center text-sm text-muted">{{ __('common.center_name') }}</p>
    </main>
</x-layouts.base>
