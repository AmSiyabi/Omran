<table class="w-full text-sm">
    <tbody>
        <tr class="total">
            <td class="px-4 py-2 font-bold text-navy">{{ __('finance.opening_balance') }}</td>
            <td class="num px-4 py-2 font-bold text-navy" dir="ltr">{{ $cashflow['opening']->toDecimalString() }}</td>
        </tr>
        <tr><td colspan="2" class="bg-cream/70 px-4 py-2 font-bold text-navy">{{ __('finance.cash_in') }}</td></tr>
        @forelse ($cashflow['inflows'] as $row)
            <tr>
                <td class="border-b border-line px-4 py-2 text-navy">{{ $row['label'] }}</td>
                <td class="num border-b border-line px-4 py-2 text-navy" dir="ltr">{{ $row['amount']->toDecimalString() }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="border-b border-line px-4 py-2 text-muted">{{ __('finance.no_data_for_period') }}</td></tr>
        @endforelse
        <tr class="total">
            <td class="bg-cream/50 px-4 py-2 font-bold text-navy">إجمالي {{ __('finance.cash_in') }}</td>
            <td class="num bg-cream/50 px-4 py-2 font-bold text-navy" dir="ltr">{{ $cashflow['inflows_total']->toDecimalString() }}</td>
        </tr>
        <tr><td colspan="2" class="bg-cream/70 px-4 py-2 font-bold text-navy">{{ __('finance.cash_out') }}</td></tr>
        @forelse ($cashflow['outflows'] as $row)
            <tr>
                <td class="border-b border-line px-4 py-2 text-navy">{{ $row['label'] }}</td>
                <td class="num border-b border-line px-4 py-2 text-navy" dir="ltr">− {{ $row['amount']->toDecimalString() }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="border-b border-line px-4 py-2 text-muted">{{ __('finance.no_data_for_period') }}</td></tr>
        @endforelse
        <tr class="total">
            <td class="bg-cream/50 px-4 py-2 font-bold text-navy">إجمالي {{ __('finance.cash_out') }}</td>
            <td class="num bg-cream/50 px-4 py-2 font-bold text-navy" dir="ltr">− {{ $cashflow['outflows_total']->toDecimalString() }}</td>
        </tr>
        <tr>
            <td class="px-4 py-2 font-bold text-navy">{{ __('finance.net_change') }}</td>
            <td class="num px-4 py-2 font-bold {{ $cashflow['net_change']->isNegative() ? 'text-error' : 'text-navy' }}" dir="ltr">{{ $cashflow['net_change']->toDecimalString() }}</td>
        </tr>
        <tr class="total">
            <td class="px-4 py-3 text-base font-bold text-navy">{{ __('finance.closing_balance') }}</td>
            <td class="num px-4 py-3 text-base font-bold text-navy" dir="ltr">{{ $cashflow['closing']->format() }}</td>
        </tr>
    </tbody>
</table>
