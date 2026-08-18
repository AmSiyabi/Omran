<div class="px-4 pt-3">
    <p class="text-sm text-muted">
        {{ $statement['partner']->display_name_ar }} ·
        {{ __('finance.opening_balance') }}: <b class="text-navy" dir="ltr">{{ $statement['opening']->toDecimalString() }}</b>
    </p>
</div>
<table class="mt-2 w-full text-sm">
    <thead>
        <tr>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.date') }}</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.description') }}</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">له</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">عليه</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.running_balance') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($statement['rows'] as $row)
            <tr>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $row['date'] }}</td>
                <td class="border-b border-line px-3 py-2 text-navy">{{ $row['description'] }}</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $row['credit']->isZero() ? '—' : $row['credit']->toDecimalString() }}</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $row['debit']->isZero() ? '—' : $row['debit']->toDecimalString() }}</td>
                <td class="num border-b border-line px-3 py-2 font-medium text-navy" dir="ltr">{{ $row['balance']->toDecimalString() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="border-b border-line px-3 py-2 text-muted">{{ __('finance.no_data_for_period') }}</td></tr>
        @endforelse
        <tr class="total">
            <td colspan="2" class="px-3 py-3 font-bold text-navy">{{ __('finance.closing_balance') }}</td>
            <td class="num px-3 py-3 font-bold text-navy" dir="ltr">{{ $statement['total_credited']->toDecimalString() }}</td>
            <td class="num px-3 py-3 font-bold text-navy" dir="ltr">{{ $statement['total_debited']->toDecimalString() }}</td>
            <td class="num px-3 py-3 font-bold text-navy" dir="ltr">{{ $statement['closing']->format() }}</td>
        </tr>
    </tbody>
</table>
