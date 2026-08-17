<div>
    {{-- الزر العائم — في متناول الإبهام (spec §12.3) --}}
    <button
        type="button"
        wire:click="show"
        class="fixed bottom-20 end-4 z-40 flex size-14 items-center justify-center rounded-full bg-gold text-navy-deep shadow-lg transition-transform hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy lg:bottom-6"
        aria-label="{{ __('finance.quick_add') }}"
    >
        <svg class="size-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>

    <x-lw-modal :show="$open" close="hide" :title="__('finance.quick_add_title')">
        <form wire:submit="save" class="space-y-4">
            <x-input
                :label="__('finance.amount')"
                name="amount"
                type="text"
                inputmode="decimal"
                wire:model="amount"
                placeholder="40.000"
                required
                autofocus
            />

            {{-- فئات بلمسة واحدة --}}
            <div class="space-y-1.5">
                <span class="block text-sm font-medium text-navy">{{ __('finance.cost_account') }}</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($categories as $categoryAccount)
                        <button
                            type="button"
                            wire:click="$set('category', '{{ $categoryAccount->code }}')"
                            wire:key="qcat-{{ $categoryAccount->code }}"
                            @class([
                                'inline-flex min-h-10 items-center rounded-full border px-3 text-sm font-medium transition-colors',
                                'border-navy bg-navy text-cream-warm' => $category === $categoryAccount->code,
                                'border-line bg-white text-navy hover:bg-navy/5' => $category !== $categoryAccount->code,
                            ])
                        >
                            {{ $categoryAccount->name_ar }}
                        </button>
                    @endforeach
                </div>
                @error('category') <p class="text-sm text-error">{{ $message }}</p> @enderror
            </div>

            @if (str_starts_with($category, '5'))
                <x-select :label="__('finance.cohort')" name="cohort_id" wire:model="cohort_id" :placeholder="__('common.choose')" required>
                    @foreach ($cohorts as $cohort)
                        <option value="{{ $cohort->id }}">{{ $cohort->code }} — {{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</option>
                    @endforeach
                </x-select>
            @endif

            <div class="space-y-1.5">
                <label for="quick-receipt" class="block text-sm font-medium text-navy">{{ __('finance.receipt_photo') }}</label>
                <input
                    type="file"
                    id="quick-receipt"
                    wire:model="receipt"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    capture="environment"
                    class="block w-full text-sm text-muted file:me-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0 file:bg-navy file:px-4 file:text-sm file:font-medium file:text-cream-warm hover:file:bg-navy-ink"
                >
                @error('receipt') <p class="text-sm text-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" variant="gold" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="hide">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>
</div>
