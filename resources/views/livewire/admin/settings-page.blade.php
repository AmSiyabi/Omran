<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl text-navy">{{ __('finance.settings') }}</h1>

    <form wire:submit="save" class="mt-6 space-y-6">
        <x-card>
            <h2 class="font-bold text-navy">{{ __('finance.settlements') }}</h2>
            <label class="mt-4 flex min-h-11 items-start gap-2 text-sm text-navy">
                <input type="checkbox" wire:model="opex_charged_to_center_pool" class="mt-1 size-4 rounded border-line">
                <span>
                    {{ __('finance.setting_opex_pool') }}
                    <span class="mt-0.5 block text-xs text-muted">{{ __('finance.setting_opex_pool_hint') }}</span>
                </span>
            </label>
        </x-card>

        <x-card>
            <h2 class="font-bold text-navy">{{ __('finance.settings_tax') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-input :label="__('finance.setting_vat_mandatory')" name="vat_mandatory" type="text" inputmode="decimal" wire:model="vat_mandatory" required />
                <x-input :label="__('finance.setting_vat_voluntary')" name="vat_voluntary" type="text" inputmode="decimal" wire:model="vat_voluntary" required />
                <x-input :label="__('finance.setting_cit_reduced_rate')" name="cit_reduced_rate" type="number" inputmode="numeric" wire:model="cit_reduced_rate" required />
                <x-input :label="__('finance.setting_cit_standard_rate')" name="cit_standard_rate" type="number" inputmode="numeric" wire:model="cit_standard_rate" required />
                <x-input :label="__('finance.setting_cit_income_limit')" name="cit_income_limit" type="text" inputmode="decimal" wire:model="cit_income_limit" required />
                <x-input :label="__('finance.setting_pit_threshold')" name="pit_threshold" type="text" inputmode="decimal" wire:model="pit_threshold" required />
                <x-input :label="__('finance.setting_pit_date')" name="pit_date" type="date" wire:model="pit_date" required />
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
        </div>
    </form>
</div>
