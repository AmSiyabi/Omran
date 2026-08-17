@php
    use App\Support\Baisa;

    $fmt = fn (int $baisa) => Baisa::format($baisa);
@endphp

<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.finance.settlements') }}" wire:navigate class="text-sm text-muted hover:text-navy">← {{ __('finance.settlements') }}</a>

    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-mono text-xs text-muted" dir="ltr">{{ $settlement->settlement_number }}</p>
            <h1 class="mt-1 text-2xl text-navy">{{ $settlement->cohort?->title_override_ar ?? $settlement->cohort?->course->title_ar }}</h1>
            @if ($snapshot['policy']['name_ar'] ?? false)
                <p class="mt-1 text-sm text-muted">{{ __('finance.per_policy', ['name' => $snapshot['policy']['name_ar']]) }}</p>
            @endif
        </div>
        <x-badge :variant="$settlement->status->badgeVariant()">{{ $settlement->status->label() }}</x-badge>
    </div>

    @unless ($isDraft)
        <p class="mt-3 rounded-lg bg-info-soft p-3 text-sm text-info">{{ __('finance.snapshot_notice') }}</p>
    @endunless

    {{-- الأعلام --}}
    @if (in_array('LOSS', $flags, true))
        <div class="mt-4 rounded-xl border border-error/30 bg-error-soft p-4">
            <h2 class="font-bold text-error">{{ __('finance.flag_loss_title') }}</h2>
            <p class="mt-1 text-sm text-navy">{{ __('finance.flag_loss_text') }}</p>
        </div>
    @endif
    @if (in_array('OVERCOMMITTED', $flags, true))
        <div class="mt-4 rounded-xl border border-warning/30 bg-warning-soft p-4">
            <h2 class="font-bold text-warning">{{ __('finance.flag_overcommitted_title') }}</h2>
            <p class="mt-1 text-sm text-navy">{{ __('finance.flag_overcommitted_text') }}</p>
        </div>
    @endif

    {{-- الملخص --}}
    <x-card class="mt-5" :padding="false">
        <dl class="divide-y divide-line text-sm">
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.gross_revenue') }}</dt>
                <dd class="font-bold text-navy">{{ $fmt($snapshot['gross_revenue_baisa'] ?? 0) }}</dd>
            </div>
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.direct_costs') }}</dt>
                <dd class="font-medium text-navy">− {{ $fmt($snapshot['direct_costs_baisa'] ?? 0) }}</dd>
            </div>
            <div class="flex justify-between gap-3 bg-cream/60 px-5 py-3">
                <dt class="font-bold text-navy">{{ __('finance.net_distributable') }}</dt>
                <dd @class(['font-bold', 'text-navy' => ($snapshot['net_distributable_baisa'] ?? 0) >= 0, 'text-error' => ($snapshot['net_distributable_baisa'] ?? 0) < 0])>
                    {{ $fmt($snapshot['net_distributable_baisa'] ?? 0) }}
                </dd>
            </div>
            @if ($snapshot['external_fee_baisa'] ?? null)
                <div class="flex justify-between gap-3 px-5 py-3">
                    <dt class="text-muted">{{ __('finance.external_fee') }}</dt>
                    <dd class="font-medium text-navy">{{ $fmt($snapshot['external_fee_baisa']) }}</dd>
                </div>
            @endif
        </dl>
    </x-card>

    {{-- توزيع المنفذين --}}
    @if (($snapshot['deliverer_allocations'] ?? []) !== [])
        <x-card class="mt-4" :padding="false">
            <x-slot:header><h2 class="font-bold text-navy">{{ __('finance.deliverer_breakdown') }}</h2></x-slot:header>
            <ul class="divide-y divide-line text-sm">
                @foreach ($snapshot['deliverer_allocations'] as $allocation)
                    <li class="flex justify-between gap-3 px-5 py-3">
                        <span class="text-navy">
                            {{ $allocation['name'] }}
                            <span class="text-xs text-muted">({{ rtrim(rtrim($allocation['weight'], '0'), '.') }}%)</span>
                        </span>
                        <span class="font-bold text-navy">{{ $fmt($allocation['amount_baisa']) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    {{-- توزيع المركز --}}
    <x-card class="mt-4" :padding="false">
        <x-slot:header>
            <div class="flex justify-between gap-3">
                <h2 class="font-bold text-navy">{{ __('finance.center_breakdown') }}</h2>
                <span class="font-bold text-navy">{{ $fmt($snapshot['center_share_baisa'] ?? 0) }}</span>
            </div>
        </x-slot:header>
        <ul class="divide-y divide-line text-sm">
            @foreach ($snapshot['center_allocations'] ?? [] as $allocation)
                <li class="flex justify-between gap-3 px-5 py-3">
                    <span class="text-navy">{{ $allocation['name'] }} <span class="text-xs text-muted">({{ rtrim(rtrim($allocation['ownership'], '0'), '.') }}%)</span></span>
                    <span @class(['font-bold', 'text-navy' => $allocation['amount_baisa'] >= 0, 'text-error' => $allocation['amount_baisa'] < 0])>
                        {{ $fmt($allocation['amount_baisa']) }}
                    </span>
                </li>
            @endforeach
        </ul>
    </x-card>

    @if ($settlement->notes_ar)
        <x-card class="mt-4">
            <p class="whitespace-pre-line text-sm text-muted">{{ $settlement->notes_ar }}</p>
        </x-card>
    @endif

    {{-- الإجراءات --}}
    <div class="mt-6 flex flex-wrap gap-2">
        @if ($isDraft)
            <x-button variant="gold" wire:click="openConfirm">{{ __('finance.confirm_settlement') }}</x-button>
            <x-button variant="secondary" wire:click="recompute" wire:loading.attr="disabled" wire:target="recompute">{{ __('finance.recompute') }}</x-button>
        @elseif ($settlement->status->value === 'posted')
            @can('finance.reverse')
                <x-button variant="danger" wire:click="openReverse">{{ __('finance.reverse_settlement') }}</x-button>
            @endcan
        @endif
    </div>

    {{-- تأكيد الترحيل — شاشة واحدة، زر واحد --}}
    <x-lw-modal :show="$confirming" close="closeConfirm" :title="__('finance.confirm_settlement_title')">
        <p class="text-sm text-muted">{{ __('finance.confirm_settlement_text') }}</p>

        @if (in_array('LOSS', $flags, true))
            <label class="mt-4 flex min-h-11 items-start gap-2 text-sm text-navy">
                <input type="checkbox" wire:model="accept_loss" class="mt-1 size-4 rounded border-line">
                {{ __('finance.accept_loss') }}
            </label>
        @endif

        @if (in_array('OVERCOMMITTED', $flags, true))
            <div class="mt-4">
                <x-input :label="__('finance.override_reason')" name="override_reason" wire:model="override_reason" required />
            </div>
        @endif

        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="gold" wire:click="confirmSettlement" wire:loading.attr="disabled" wire:target="confirmSettlement">
                {{ __('common.confirm') }}
            </x-button>
            <x-button variant="ghost" wire:click="closeConfirm">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>

    {{-- عكس التصفية --}}
    <x-lw-modal :show="$reversing" close="closeReverse" :title="__('finance.reverse_settlement_title', ['number' => $settlement->settlement_number])" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('finance.reverse_settlement_warning') }}</p>
        <div class="mt-4">
            <x-input :label="__('finance.reverse_reason')" name="reverse_reason" wire:model="reverse_reason" required />
        </div>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="reverseSettlement" wire:loading.attr="disabled" wire:target="reverseSettlement">{{ __('finance.reverse_settlement') }}</x-button>
            <x-button variant="ghost" wire:click="closeReverse">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
