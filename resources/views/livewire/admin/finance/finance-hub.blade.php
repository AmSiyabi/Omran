<div class="mx-auto max-w-4xl">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('finance.finance') }}</h1>
        @can('finance.settle')
            <x-button variant="secondary" size="sm" :href="route('admin.finance.settlements')" wire:navigate>
                {{ __('finance.settlements') }}
            </x-button>
        @endcan
    </div>

    {{-- الإجراءات — أحداث تجارية، لا مدين/دائن --}}
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ([
            'revenue' => ['perm' => 'finance.record_income', 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
            'payment' => ['perm' => 'finance.record_income', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5z'],
            'direct_cost' => ['perm' => 'finance.record_expense', 'icon' => 'M15 8.25H9m6 3H9m3 6-3-3h1.5a3 3 0 1 0 0-6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
            'opex' => ['perm' => 'finance.record_expense', 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21'],
            'payout' => ['perm' => 'partners.record_payout', 'icon' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9'],
            'contribution' => ['perm' => 'partners.record_payout', 'icon' => 'M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        ] as $key => $meta)
            @can($meta['perm'])
                <button
                    type="button"
                    wire:click="open('{{ $key }}')"
                    class="card-lift flex min-h-24 flex-col items-center justify-center gap-2 rounded-xl border border-line bg-white p-4 text-center"
                >
                    <svg class="size-6 text-gold-deep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}" />
                    </svg>
                    <span class="text-sm font-bold text-navy">{{ __('finance.record_'.($key === 'direct_cost' ? 'direct_cost' : $key)) }}</span>
                </button>
            @endcan
        @endforeach
    </div>

    {{-- أحدث الحركات --}}
    <x-card class="mt-6" :padding="false">
        <x-slot:header>
            <h2 class="font-bold text-navy">{{ __('finance.recent_entries') }}</h2>
        </x-slot:header>

        @if ($entries->isEmpty())
            <x-empty-state :title="__('finance.no_entries')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($entries as $entry)
                    <li class="px-4 py-3 sm:px-5" wire:key="entry-{{ $entry->id }}" x-data="{ openLines: false }">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" class="min-w-0 flex-1 text-start" x-on:click="openLines = !openLines">
                                <p class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-muted" dir="ltr">{{ $entry->entry_number }}</span>
                                    <x-badge :variant="$entry->status->value === 'posted' ? 'success' : 'error'">{{ $entry->status->label() }}</x-badge>
                                </p>
                                <p class="mt-0.5 truncate text-sm font-medium text-navy">{{ $entry->description_ar }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $entry->entry_date->format('Y-m-d') }}</p>
                            </button>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="text-sm font-bold text-navy">
                                    {{ \App\Support\Baisa::format($entry->lines->sum(fn ($l) => $l->debit_baisa->baisa)) }}
                                </span>
                                @can('finance.reverse')
                                    @if ($entry->status->value === 'posted')
                                        <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmReverse({{ $entry->id }})">
                                            {{ __('finance.reverse_entry') }}
                                        </x-button>
                                    @endif
                                @endcan
                            </div>
                        </div>

                        <div x-show="openLines" x-cloak class="mt-3 rounded-lg bg-cream/60 p-3">
                            <ul class="space-y-1 text-xs">
                                @foreach ($entry->lines as $line)
                                    <li class="flex justify-between gap-3" wire:key="line-{{ $line->id }}">
                                        <span class="text-navy">{{ $line->account->code }} — {{ $line->account->name_ar }}</span>
                                        <span class="shrink-0 font-medium {{ $line->debit_baisa->isPositive() ? 'text-navy' : 'text-muted' }}">
                                            {{ $line->debit_baisa->isPositive() ? __('finance.debit').' '.$line->debit_baisa->format() : __('finance.credit').' '.$line->credit_baisa->format() }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>

    {{-- نموذج الإجراء --}}
    <x-lw-modal :show="$action !== null" close="close" :title="$action ? __('finance.record_'.$action) : ''" maxWidth="lg">
        @if ($action)
            <p class="mb-4 rounded-lg bg-info-soft p-3 text-sm text-info">{{ __('finance.action_hint_'.$action) }}</p>

            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input :label="__('finance.amount')" name="amount" type="text" inputmode="decimal" wire:model="amount" required />
                    <x-input :label="__('finance.date')" name="date" type="date" wire:model="date" required />
                </div>

                @if (in_array($action, ['revenue', 'direct_cost'], true))
                    <x-select :label="__('finance.cohort')" name="cohort_id" wire:model="cohort_id" :placeholder="__('common.choose')" required>
                        @foreach ($cohorts as $cohort)
                            <option value="{{ $cohort->id }}">{{ $cohort->code }} — {{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</option>
                        @endforeach
                    </x-select>
                @elseif ($action === 'payment')
                    <x-select :label="__('finance.cohort')" name="cohort_id" wire:model="cohort_id">
                        <option value="">{{ __('finance.no_cohort') }}</option>
                        @foreach ($cohorts as $cohort)
                            <option value="{{ $cohort->id }}">{{ $cohort->code }} — {{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</option>
                        @endforeach
                    </x-select>
                @endif

                @if ($action === 'revenue')
                    <x-select :label="__('finance.revenue_account')" name="account_code" wire:model="account_code" required>
                        @foreach ($revenueAccounts as $account)
                            <option value="{{ $account->code }}">{{ $account->name_ar }}</option>
                        @endforeach
                    </x-select>
                @elseif ($action === 'direct_cost')
                    <x-select :label="__('finance.cost_account')" name="account_code" wire:model="account_code" required>
                        @foreach ($costAccounts as $account)
                            <option value="{{ $account->code }}">{{ $account->name_ar }}</option>
                        @endforeach
                    </x-select>
                @elseif ($action === 'opex')
                    <x-select :label="__('finance.expense_account')" name="account_code" wire:model="account_code" required>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->code }}">{{ $account->name_ar }}</option>
                        @endforeach
                    </x-select>
                @elseif (in_array($action, ['payout', 'contribution'], true))
                    <x-select :label="__('finance.partner')" name="partner_id" wire:model="partner_id" :placeholder="__('common.choose')" required>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->display_name_ar }}</option>
                        @endforeach
                    </x-select>
                @endif

                @if ($action !== 'revenue')
                    <x-select :label="in_array($action, ['payment', 'contribution'], true) ? __('finance.cash_account_into') : __('finance.cash_account_from')" name="cash_code" wire:model="cash_code" required>
                        @foreach ($cashAccounts as $account)
                            <option value="{{ $account->code }}">{{ $account->name_ar }}</option>
                        @endforeach
                    </x-select>
                @endif

                <x-input :label="__('finance.description')" name="description" wire:model="description" required />

                @if (in_array($action, ['direct_cost', 'opex'], true))
                    <div class="space-y-1.5">
                        <label for="receipt" class="block text-sm font-medium text-navy">{{ __('finance.receipt_photo') }}</label>
                        <input
                            type="file"
                            id="receipt"
                            wire:model="receipt"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="block w-full text-sm text-muted file:me-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0 file:bg-navy file:px-4 file:text-sm file:font-medium file:text-cream-warm hover:file:bg-navy-ink"
                        >
                        <p class="text-sm text-muted">{{ __('finance.receipt_hint') }}</p>
                        @error('receipt') <p class="text-sm text-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row">
                    <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                    <x-button variant="ghost" type="button" wire:click="close">{{ __('common.cancel') }}</x-button>
                </div>
            </form>
        @endif
    </x-lw-modal>

    {{-- عكس قيد --}}
    <x-lw-modal :show="$reversingId !== null" close="cancelReverse" :title="$reversingEntry ? __('finance.reverse_entry_title', ['number' => $reversingEntry->entry_number]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('finance.reverse_entry_warning') }}</p>
        <div class="mt-4">
            <x-input :label="__('finance.reverse_reason')" name="reverse_reason" wire:model="reverse_reason" required />
        </div>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="reverseEntry" wire:loading.attr="disabled" wire:target="reverseEntry">{{ __('finance.reverse_entry') }}</x-button>
            <x-button variant="ghost" wire:click="cancelReverse">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
