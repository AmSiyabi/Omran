<table class="w-full text-sm">
    <thead>
        <tr>
            <th class="bg-navy px-4 py-2 text-start text-cream-warm">{{ __('finance.account') }}</th>
            <th class="bg-navy px-4 py-2 text-start text-cream-warm">{{ __('finance.amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach (['revenue' => __('finance.revenue'), 'direct_costs' => __('finance.direct_costs'), 'opex' => __('finance.operating_expenses')] as $section => $label)
            <tr><td colspan="2" class="bg-cream/70 px-4 py-2 font-bold text-navy">{{ $label }} @if($section === 'revenue' && $income['basis'] === 'cash') ({{ __('finance.basis_cash') }}) @endif</td></tr>
            @forelse ($income[$section] as $line)
                <tr>
                    <td class="border-b border-line px-4 py-2 text-navy">{{ $line['code'] }} — {{ $line['name_ar'] }}</td>
                    <td class="num border-b border-line px-4 py-2 font-medium text-navy" dir="ltr">{{ $line['amount']->toDecimalString() }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="border-b border-line px-4 py-2 text-muted">{{ __('finance.no_data_for_period') }}</td></tr>
            @endforelse
            <tr class="total">
                <td class="border-b border-line bg-cream/50 px-4 py-2 font-bold text-navy">إجمالي {{ $label }}</td>
                <td class="num border-b border-line bg-cream/50 px-4 py-2 font-bold text-navy" dir="ltr">{{ $income[$section.'_total']->toDecimalString() }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="px-4 py-3 text-base font-bold text-navy">{{ __('finance.net_profit') }}</td>
            <td class="num px-4 py-3 text-base font-bold {{ $income['net']->isNegative() ? 'text-error' : 'text-navy' }}" dir="ltr">{{ $income['net']->format() }}</td>
        </tr>
    </tbody>
</table>
