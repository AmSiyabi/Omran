<div class="mx-auto max-w-3xl">
    <h1 class="text-2xl text-navy">{{ __('auth.security_title') }}</h1>

    {{-- التحقق بخطوتين --}}
    <x-card class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-navy">{{ __('auth.two_factor_title') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('auth.two_factor_status_hint') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if ($user->hasConfirmedTwoFactor())
                    <x-badge variant="success">{{ __('common.enabled') }}</x-badge>
                @else
                    <x-badge variant="warning">{{ __('common.not_enabled') }}</x-badge>
                @endif
                <x-button href="{{ route('admin.security.two-factor') }}" variant="secondary" size="sm" wire:navigate>
                    {{ __('common.manage') }}
                </x-button>
            </div>
        </div>
    </x-card>

    {{-- الجلسات النشطة --}}
    <x-card class="mt-6" :padding="false">
        <x-slot:header>
            <h2 class="font-bold text-navy">{{ __('auth.sessions_title') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('auth.sessions_hint') }}</p>
        </x-slot:header>

        <ul class="divide-y divide-line">
            @forelse ($this->sessions as $session)
                <li class="flex items-center justify-between gap-4 px-5 py-4" wire:key="session-{{ $session['id'] }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <svg class="size-8 shrink-0 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                        </svg>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-navy">
                                {{ $session['browser'] }}
                                @if ($session['is_current'])
                                    <x-badge variant="info" class="ms-1">{{ __('auth.current_session') }}</x-badge>
                                @endif
                            </p>
                            <p class="truncate text-xs text-muted" dir="ltr">
                                {{ $session['ip'] }} · {{ $session['last_active']->format('Y-m-d H:i') }}
                            </p>
                        </div>
                    </div>

                    @unless ($session['is_current'])
                        <x-button
                            variant="ghost"
                            size="sm"
                            wire:click="confirmRevoke('{{ $session['id'] }}')"
                            class="text-error"
                        >
                            {{ __('auth.revoke_session') }}
                        </x-button>
                    @endunless
                </li>
            @empty
                <li>
                    <x-empty-state :title="__('common.no_results')" />
                </li>
            @endforelse
        </ul>
    </x-card>

    {{-- تأكيد إنهاء الجلسة — يسمي الجهاز المستهدف --}}
    <x-lw-modal :show="$revokingSessionId !== null" close="cancelRevoke" :title="__('auth.revoke_session_title')" maxWidth="sm">
        @if ($this->revokingSession !== null)
            <p class="text-sm text-navy">
                {{ __('auth.revoke_session_body', ['browser' => $this->revokingSession['browser']]) }}
            </p>
            <p class="mt-1 text-xs text-muted" dir="ltr">{{ $this->revokingSession['ip'] }} · {{ $this->revokingSession['last_active']->format('Y-m-d H:i') }}</p>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
                <x-button variant="danger" target="revoke" wire:click="revoke">{{ __('auth.revoke_session') }}</x-button>
                <x-button variant="ghost" wire:click="cancelRevoke">{{ __('common.cancel') }}</x-button>
            </div>
        @endif
    </x-lw-modal>
</div>
