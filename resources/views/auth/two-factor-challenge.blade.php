<x-layouts.auth :title="__('auth.two_factor_title')">
    <div x-data="{ recovery: false }">
        <p class="mb-4 text-sm text-muted" x-show="!recovery">
            {{ __('auth.two_factor_hint') }}
        </p>
        <p class="mb-4 text-sm text-muted" x-show="recovery" x-cloak>
            {{ __('auth.recovery_code_hint') }}
        </p>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div x-show="!recovery">
                <x-input
                    :label="__('auth.two_factor_code')"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                />
            </div>

            <div x-show="recovery" x-cloak>
                <x-input
                    :label="__('auth.recovery_code')"
                    name="recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                />
            </div>

            <x-button type="submit" class="w-full">{{ __('auth.verify') }}</x-button>

            <p class="text-center">
                <button
                    type="button"
                    class="text-sm text-info underline-offset-4 hover:underline"
                    x-on:click="recovery = !recovery"
                >
                    <span x-show="!recovery">{{ __('auth.use_recovery_code') }}</span>
                    <span x-show="recovery" x-cloak>{{ __('auth.use_totp_code') }}</span>
                </button>
            </p>
        </form>
    </div>
</x-layouts.auth>
