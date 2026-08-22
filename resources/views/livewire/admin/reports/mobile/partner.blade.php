{{-- كشف حساب الشريك — بطاقات الجوال (< md) --}}
<div class="px-4 pt-3">
    <p class="text-sm text-muted">
        {{ $statement['partner']->display_name_ar }} ·
        {{ __('finance.opening_balance') }}: <b class="text-navy" dir="ltr">{{ $statement['opening']->toDecimalString() }}</b>
    </p>
</div>

@if ($statement['rows'] === [])
    <p class="px-4 py-6 text-muted">{{ __('finance.no_data_for_period') }}</p>
@else
    <ul class="mt-2 divide-y divide-line">
        @foreach ($statement['rows'] as $row)
            <li class="px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-navy">{{ $row['description'] }}</p>
                        <p class="mt-0.5 text-xs text-muted" dir="ltr">{{ $row['date'] }} · {{ $row['entry_number'] }}</p>
                    </div>
                    <div class="shrink-0 text-end">
                        @unless ($row['credit']->isZero())
                            <p class="text-sm font-medium text-success" dir="ltr">{{ $row['credit']->toDecimalString() }}</p>
                        @endunless
                        @unless ($row['debit']->isZero())
                            <p class="text-sm font-medium text-error" dir="ltr">− {{ $row['debit']->toDecimalString() }}</p>
                        @endunless
                        <p class="mt-0.5 text-xs text-muted" dir="ltr">{{ $row['balance']->toDecimalString() }}</p>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif

<div class="flex items-center justify-between border-t border-line px-4 py-3">
    <span class="font-bold text-navy">{{ __('finance.closing_balance') }}</span>
    <span class="font-bold text-navy" dir="ltr">{{ $statement['closing']->format() }}</span>
</div>
