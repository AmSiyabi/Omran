<div class="px-4 pt-3">
    <p class="text-sm text-muted">{{ __('finance.year') }}: <b class="text-navy" dir="ltr">{{ $annual['year'] }}</b> — {{ __('finance.report_annual') }} (جاهزية ضريبة دخل الأفراد 2028)</p>
</div>
<table class="mt-2 w-full text-sm">
    <thead>
        <tr>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.partner') }}</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.allocated_total') }}</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.paid_out_total') }}</th>
            <th class="bg-navy px-3 py-2 text-start text-cream-warm">صافي الحركة</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($annual['rows'] as $row)
            <tr>
                <td class="border-b border-line px-3 py-2 font-medium text-navy">{{ $row['partner']->display_name_ar }}</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $row['allocated']->toDecimalString() }}</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $row['paid_out']->toDecimalString() }}</td>
                <td class="num border-b border-line px-3 py-2 font-bold text-navy" dir="ltr">{{ $row['net_movement']->toDecimalString() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
