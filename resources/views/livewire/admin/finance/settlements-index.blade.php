<div class="mx-auto max-w-4xl">
    <a href="{{ route('admin.finance') }}" wire:navigate class="text-sm text-muted hover:text-navy">← {{ __('finance.finance') }}</a>
    <h1 class="mt-2 text-2xl text-navy">{{ __('finance.settlements') }}</h1>

    {{-- دفعات منفذة بانتظار التصفية --}}
    <x-card class="mt-5" :padding="false">
        <x-slot:header>
            <h2 class="font-bold text-navy">{{ __('finance.ready_to_settle') }}</h2>
        </x-slot:header>

        @if ($readyCohorts->isEmpty())
            <x-empty-state :title="__('finance.no_ready_cohorts')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($readyCohorts as $cohort)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="ready-{{ $cohort->id }}">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-navy">{{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</p>
                            <p class="mt-0.5 text-xs text-muted" dir="ltr">{{ $cohort->code }}</p>
                        </div>
                        <x-button size="sm" variant="gold" wire:click="computeDraft({{ $cohort->id }})" wire:loading.attr="disabled" wire:target="computeDraft">
                            {{ __('finance.settle_cohort') }}
                        </x-button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    {{-- سجل التصفيات --}}
    <x-card class="mt-5" :padding="false">
        @if ($settlements->isEmpty())
            <x-empty-state :title="__('finance.no_settlements')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($settlements as $settlement)
                    <li wire:key="settlement-{{ $settlement->id }}">
                        <a href="{{ route('admin.finance.settlements.show', $settlement->id) }}" wire:navigate class="flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-navy/[0.03] sm:px-5">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-muted" dir="ltr">{{ $settlement->settlement_number }}</span>
                                    <x-badge :variant="$settlement->status->badgeVariant()">{{ $settlement->status->label() }}</x-badge>
                                </p>
                                <p class="mt-0.5 truncate text-sm font-medium text-navy">
                                    {{ $settlement->cohort?->title_override_ar ?? $settlement->cohort?->course->title_ar ?? __('finance.settlement_type.monthly') }}
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-navy">{{ $settlement->net_distributable_baisa->format() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4">
        {{ $settlements->links() }}
    </div>
</div>
