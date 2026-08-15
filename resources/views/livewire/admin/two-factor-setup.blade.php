<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl text-navy">{{ __('auth.two_factor_title') }}</h1>

    @if ($required && ! $confirmed)
        <div class="mt-4 rounded-lg border border-warning/30 bg-warning-soft p-4">
            <h2 class="font-bold text-warning">{{ __('auth.two_factor_required_title') }}</h2>
            <p class="mt-1 text-sm text-navy">{{ __('auth.two_factor_required_hint') }}</p>
        </div>
    @endif

    {{-- التفعيل --}}
    @if (! $enabled)
        <x-card class="mt-6">
            <p class="text-sm text-muted">{{ __('auth.two_factor_hint_setup') }}</p>

            <form wire:submit="enable" class="mt-4 space-y-4">
                <x-input
                    :label="__('auth.password_label')"
                    name="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    required
                />
                <x-button type="submit" target="enable" :loading-label="__('common.loading')">
                    {{ __('auth.two_factor_enable') }}
                </x-button>
            </form>
        </x-card>
    @endif

    {{-- تأكيد الرمز --}}
    @if ($enabled && ! $confirmed)
        <x-card class="mt-6">
            <h2 class="font-bold text-navy">{{ __('auth.two_factor_enabled') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('auth.two_factor_scan_qr') }}</p>

            <div class="mt-4 flex justify-center rounded-lg bg-white p-4" dir="ltr">
                {!! $qrCodeSvg !!}
            </div>

            @if ($secretKey)
                <p class="mt-3 text-center text-sm text-muted">
                    {{ __('auth.two_factor_secret_key') }}:
                    <code class="rounded bg-navy/5 px-2 py-0.5 font-mono text-navy" dir="ltr">{{ $secretKey }}</code>
                </p>
            @endif

            <form wire:submit="confirm" class="mt-4 space-y-4">
                <x-input
                    :label="__('auth.two_factor_code')"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    wire:model="code"
                    required
                />
                <x-button type="submit" target="confirm" :loading-label="__('common.loading')">
                    {{ __('auth.verify') }}
                </x-button>
            </form>
        </x-card>
    @endif

    {{-- رموز الاسترداد — تظهر مرة واحدة --}}
    @if (count($recoveryCodes) > 0)
        <x-card class="mt-6 border-gold/40">
            <h2 class="font-bold text-navy">{{ __('auth.recovery_codes_title') }}</h2>
            <p class="mt-1 text-sm text-error">{{ __('auth.recovery_codes_hint') }}</p>

            <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2" dir="ltr">
                @foreach ($recoveryCodes as $recoveryCode)
                    <li class="rounded bg-navy/5 px-3 py-2 text-center font-mono text-sm text-navy" wire:key="rc-{{ $loop->index }}">
                        {{ $recoveryCode }}
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    {{-- الإدارة بعد التأكيد --}}
    @if ($confirmed)
        <x-card class="mt-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-navy">{{ __('auth.two_factor_title') }}</h2>
                <x-badge variant="success">{{ __('common.enabled') }}</x-badge>
            </div>

            <form wire:submit="regenerateRecoveryCodes" class="mt-5 space-y-4">
                <x-input
                    :label="__('auth.password_label')"
                    name="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    :hint="__('auth.two_factor_manage_hint')"
                    required
                />
                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" variant="secondary" target="regenerateRecoveryCodes" :loading-label="__('common.loading')">
                        {{ __('auth.recovery_codes_regenerate') }}
                    </x-button>

                    @unless ($required)
                        <x-button
                            type="button"
                            variant="danger"
                            wire:click="disable"
                            wire:loading.attr="disabled"
                            wire:target="disable"
                        >
                            {{ __('auth.two_factor_disable') }}
                        </x-button>
                    @endunless
                </div>
            </form>
        </x-card>
    @endif
</div>
