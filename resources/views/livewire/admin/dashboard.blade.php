<div>
    <h1 class="text-2xl text-navy">{{ __('common.welcome') }}، {{ $user->name_ar }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('common.center_name') }}</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-card>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-navy">{{ __('common.nav_finance') }}</h2>
                <x-badge variant="gold">{{ __('common.coming_soon') }}</x-badge>
            </div>
            <p class="mt-2 text-sm text-muted">{{ __('common.dashboard_finance_placeholder') }}</p>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-navy">{{ __('common.nav_courses') }}</h2>
                <x-badge variant="gold">{{ __('common.coming_soon') }}</x-badge>
            </div>
            <p class="mt-2 text-sm text-muted">{{ __('common.dashboard_courses_placeholder') }}</p>
        </x-card>

        <x-card class="sm:col-span-2 xl:col-span-1">
            <h2 class="text-base font-bold text-navy">{{ __('auth.security_title') }}</h2>
            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="text-sm text-muted">{{ __('auth.two_factor_title') }}</span>
                @if ($user->hasConfirmedTwoFactor())
                    <x-badge variant="success">{{ __('common.enabled') }}</x-badge>
                @else
                    <x-badge variant="warning">{{ __('common.not_enabled') }}</x-badge>
                @endif
            </div>
            <div class="mt-4">
                <x-button href="{{ route('admin.security') }}" variant="secondary" size="sm" wire:navigate>
                    {{ __('common.manage') }}
                </x-button>
            </div>
        </x-card>
    </div>
</div>
