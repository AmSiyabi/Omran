<div class="mx-auto max-w-5xl">
    <h1 class="text-2xl text-navy">{{ __('finance.reports') }}</h1>

    {{-- اختيار التقرير --}}
    <nav class="scrollbar-none -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        @foreach (App\Livewire\Admin\Reports\ReportsHub::REPORTS as $key)
            <button
                type="button"
                wire:click="selectReport('{{ $key }}')"
                wire:key="tab-{{ $key }}"
                @class([
                    'inline-flex min-h-10 shrink-0 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                    'border-navy bg-navy text-cream-warm' => $report === $key,
                    'border-line bg-white text-navy hover:bg-navy/5' => $report !== $key,
                ])
            >
                {{ __('finance.report_'.$key) }}
            </button>
        @endforeach
    </nav>

    {{-- المرشحات --}}
    <div class="mt-4 flex flex-wrap items-end gap-3">
        @if (in_array($report, ['income', 'cashflow', 'partner'], true))
            <x-input :label="__('finance.period_from')" name="from" type="date" wire:model.live="from" />
            <x-input :label="__('finance.period_to')" name="to" type="date" wire:model.live="to" />
        @endif

        @if ($report === 'income')
            <x-select :label="null" name="basis" wire:model.live="basis">
                <option value="accrual">{{ __('finance.basis_accrual') }}</option>
                <option value="cash">{{ __('finance.basis_cash') }}</option>
            </x-select>
        @endif

        @if ($report === 'partner')
            <x-select :label="__('finance.partner')" name="partnerId" wire:model.live="partnerId" :placeholder="__('common.choose')">
                @foreach ($partners as $partnerOption)
                    <option value="{{ $partnerOption->id }}">{{ $partnerOption->display_name_ar }}</option>
                @endforeach
            </x-select>
        @endif

        @if ($report === 'annual')
            <x-input :label="__('finance.year')" name="year" type="number" inputmode="numeric" wire:model.live="year" />
        @endif

        @if ($report === 'aging')
            <x-input :label="__('finance.period_to')" name="to" type="date" wire:model.live="to" />
        @endif

        @can('reports.export')
            <div class="ms-auto flex gap-2">
                <x-button variant="secondary" size="sm" wire:click="exportXlsx" wire:loading.attr="disabled" wire:target="exportXlsx">{{ __('courses.export_xlsx') }}</x-button>
                <x-button variant="secondary" size="sm" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf">{{ __('finance.export_pdf') }}</x-button>
            </div>
        @endcan
    </div>

    <x-card class="mt-4" :padding="false">
        {{-- قائمة الدخل --}}
        @if ($report === 'income')
            @include('livewire.admin.reports.pdf.income')
        @elseif ($report === 'cashflow')
            @include('livewire.admin.reports.pdf.cashflow')
        @elseif ($report === 'cohorts')
            @include('livewire.admin.reports.pdf.cohorts')
        @elseif ($report === 'partner')
            @if ($statement === null)
                <x-empty-state :title="__('common.choose')" :description="__('finance.report_partner')" />
            @else
                @include('livewire.admin.reports.pdf.partner')
            @endif
        @elseif ($report === 'aging')
            @include('livewire.admin.reports.pdf.aging')
        @elseif ($report === 'annual')
            @include('livewire.admin.reports.pdf.annual')
        @elseif ($report === 'vat')
            @include('livewire.admin.reports.pdf.vat')
        @endif
    </x-card>
</div>
