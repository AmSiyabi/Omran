<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl text-navy">{{ __('finance.tax_estimate_title') }} — {{ $estimate['year'] }}</h1>

    {{-- القيمة المضافة --}}
    <x-card class="mt-5" :padding="false">
        <dl class="divide-y divide-line text-sm">
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.vat_gauge_title') }}</dt>
                <dd class="num font-bold text-navy" dir="ltr">{{ $estimate['vat']['taxable']->format() }}</dd>
            </div>
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.tax_vat_status') }}</dt>
                <dd class="font-medium text-navy">{{ __('finance.tax_not_registered') }}</dd>
            </div>
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.tax_mandatory_threshold') }}</dt>
                <dd class="num text-navy" dir="ltr">{{ $estimate['vat']['mandatory']->format() }}</dd>
            </div>
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.tax_remaining_to_threshold') }}</dt>
                <dd class="num font-bold text-navy" dir="ltr">{{ $estimate['vat_remaining_to_mandatory']->format() }}</dd>
            </div>
        </dl>
    </x-card>

    {{-- ضريبة الدخل --}}
    <x-card class="mt-4" :padding="false">
        <dl class="divide-y divide-line text-sm">
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.tax_net_profit_ytd') }}</dt>
                <dd class="num font-bold {{ $estimate['net_profit_ytd']->isNegative() ? 'text-error' : 'text-navy' }}" dir="ltr">{{ $estimate['net_profit_ytd']->format() }}</dd>
            </div>
            <div class="flex justify-between gap-3 px-5 py-3">
                <dt class="text-muted">{{ __('finance.tax_cit_bracket') }}</dt>
                <dd class="font-medium text-navy">
                    {{ $estimate['cit_reduced_applied']
                        ? __('finance.tax_cit_reduced', ['rate' => $estimate['cit_rate_percent']])
                        : __('finance.tax_cit_standard', ['rate' => $estimate['cit_rate_percent']]) }}
                </dd>
            </div>
            <div class="flex justify-between gap-3 bg-cream/60 px-5 py-3">
                <dt class="font-bold text-navy">{{ __('finance.tax_estimate_amount') }}</dt>
                <dd class="num font-bold text-navy" dir="ltr">{{ $estimate['cit_estimate']->format() }}</dd>
            </div>
        </dl>
    </x-card>

    <p class="mt-3 rounded-lg bg-info-soft p-3 text-xs text-info">{{ __('finance.tax_cit_assumption') }}</p>

    <p class="mt-2 rounded-lg bg-cream/70 p-3 text-xs text-muted">
        {{ __('finance.tax_pit_note', [
            'rate' => $estimate['pit_rate_percent'],
            'threshold' => $estimate['pit_threshold']->toDecimalString(),
            'date' => $estimate['pit_effective_date'],
        ]) }}
    </p>

    {{-- إخلاء المسؤولية الإلزامي — غير قابل للإزالة (spec §8.9) --}}
    <div class="mt-4 rounded-xl border border-warning/40 bg-warning-soft p-4 text-center">
        <p class="font-bold text-warning">{{ __('finance.tax_disclaimer') }}</p>
    </div>
</div>
