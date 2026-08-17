<div>
    <x-card :padding="false">
        <x-slot:header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-navy">{{ __('courses.registration_links') }}</h2>
                @can('create', App\Models\RegistrationLink::class)
                    <x-button variant="secondary" size="sm" wire:click="create">{{ __('courses.new_link') }}</x-button>
                @endcan
            </div>
        </x-slot:header>

        @if ($links->isEmpty())
            <x-empty-state :title="__('courses.no_links')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($links as $link)
                    <li class="px-4 py-3 sm:px-5" wire:key="link-{{ $link->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-navy">{{ $link->label_ar ?? __('courses.registration_links') }}</span>
                                    @if (! $link->is_active)
                                        <x-badge variant="error">{{ __('courses.link_inactive_badge') }}</x-badge>
                                    @elseif ($link->isExpired())
                                        <x-badge variant="warning">{{ __('courses.link_expired_badge') }}</x-badge>
                                    @elseif ($link->isExhausted())
                                        <x-badge variant="warning">{{ __('courses.join_unusable_exhausted') }}</x-badge>
                                    @else
                                        <x-badge variant="success">{{ __('courses.link_active_badge') }}</x-badge>
                                    @endif
                                    @if ($link->requires_approval)
                                        <x-badge variant="info">{{ __('courses.link_requires_approval') }}</x-badge>
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-muted">
                                    {{ __('courses.link_uses') }}: {{ $link->uses_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}
                                    @if ($link->price_override_baisa !== null)
                                        · {{ __('courses.link_price_override') }}: {{ \App\Support\Baisa::toString($link->price_override_baisa) }}
                                    @endif
                                    @if ($link->expires_at)
                                        · {{ __('courses.link_expires_at') }}: {{ $link->expires_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
                                    @endif
                                </p>
                                <p class="mt-1 truncate font-mono text-xs text-muted" dir="ltr">{{ $link->url() }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1" x-data="{ copied: false }">
                                <x-button
                                    variant="ghost"
                                    size="sm"
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText(@js($link->url())).then(() => { copied = true; window.toast('success', @js(__('courses.link_copied'))); setTimeout(() => copied = false, 1500) })"
                                >
                                    <span x-show="!copied">{{ __('courses.link_copy') }}</span>
                                    <span x-show="copied" x-cloak>✓</span>
                                </x-button>

                                @if ($link->is_active)
                                    @can('revoke', $link)
                                        <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmRevoke({{ $link->id }})">{{ __('courses.link_revoke') }}</x-button>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    {{-- إنشاء رابط --}}
    <x-lw-modal :show="$showForm" close="closeForm" :title="__('courses.new_link')">
        <form wire:submit="save" class="space-y-4">
            <x-input :label="__('courses.link_label')" name="label_ar" wire:model="label_ar" :hint="__('courses.link_label_hint')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input :label="__('courses.link_price_override')" name="price_override" type="text" inputmode="decimal" wire:model="price_override" :hint="__('courses.link_price_override_hint')" />
                <x-input :label="__('courses.link_max_uses')" name="max_uses" type="number" inputmode="numeric" wire:model="max_uses" :hint="__('common.optional')" />
            </div>
            <x-input :label="__('courses.link_expires_at')" name="expires_at" type="datetime-local" wire:model="expires_at" :hint="__('common.optional')" />

            <label class="flex min-h-11 items-center gap-2 text-sm text-navy">
                <input type="checkbox" wire:model="requires_approval" class="size-4 rounded border-line">
                {{ __('courses.link_requires_approval') }}
            </label>

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>

    {{-- تأكيد الإيقاف --}}
    <x-lw-modal :show="$revokingId !== null" close="cancelRevoke" :title="$revokingLink ? __('courses.link_revoke_title', ['label' => $revokingLink->label_ar ?? $revokingLink->token]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('courses.link_revoke_warning') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="revoke" wire:loading.attr="disabled" wire:target="revoke">{{ __('courses.link_revoke') }}</x-button>
            <x-button variant="ghost" wire:click="cancelRevoke">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
