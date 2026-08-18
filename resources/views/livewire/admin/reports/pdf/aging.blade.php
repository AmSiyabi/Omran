@if ($aging['rows'] === [])
    <p class="px-4 py-6 text-muted">{{ __('finance.no_data_for_period') }}</p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.cohort') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.aging_0_30') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.aging_31_60') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.aging_61_90') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.aging_90_plus') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aging['rows'] as $row)
                <tr>
                    <td class="border-b border-line px-3 py-2 text-navy">{{ $row['label'] }}</td>
                    @foreach (['0_30', '31_60', '61_90', '90_plus'] as $bucket)
                        <td class="num border-b border-line px-3 py-2 {{ $bucket === '90_plus' && $row['buckets'][$bucket]->isPositive() ? 'font-bold text-error' : 'text-navy' }}" dir="ltr">
                            {{ $row['buckets'][$bucket]->isZero() ? '—' : $row['buckets'][$bucket]->toDecimalString() }}
                        </td>
                    @endforeach
                    <td class="num border-b border-line px-3 py-2 font-bold text-navy" dir="ltr">{{ $row['total']->toDecimalString() }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td class="px-3 py-3 font-bold text-navy">الإجمالي</td>
                @foreach (['0_30', '31_60', '61_90', '90_plus'] as $bucket)
                    <td class="num px-3 py-3 font-bold text-navy" dir="ltr">{{ $aging['totals'][$bucket]->toDecimalString() }}</td>
                @endforeach
                <td class="num px-3 py-3 font-bold text-navy" dir="ltr">{{ $aging['grand_total']->format() }}</td>
            </tr>
        </tbody>
    </table>
@endif
