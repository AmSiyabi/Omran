{{-- الدخل السنوي للشريكين — بطاقات الجوال (< md) --}}
<div class="px-4 pt-3">
    <p class="text-sm text-muted">{{ __('finance.year') }}: <b class="text-navy" dir="ltr">{{ $annual['year'] }}</b> — {{ __('finance.report_annual') }} (جاهزية ضريبة دخل الأفراد 2028)</p>
</div>
<ul class="mt-2 divide-y divide-line">
    @foreach ($annual['rows'] as $row)
        <li class="space-y-2 px-4 py-4">
            <p class="font-medium text-navy">{{ $row['partner']->display_name_ar }}</p>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <dt class="text-muted">{{ __('finance.allocated_total') }}</dt>
                <dd class="text-end text-navy" dir="ltr">{{ $row['allocated']->toDecimalString() }}</dd>
                <dt class="text-muted">{{ __('finance.paid_out_total') }}</dt>
                <dd class="text-end text-navy" dir="ltr">{{ $row['paid_out']->toDecimalString() }}</dd>
                <dt class="text-muted">صافي الحركة</dt>
                <dd class="text-end font-bold text-navy" dir="ltr">{{ $row['net_movement']->toDecimalString() }}</dd>
            </dl>
        </li>
    @endforeach
</ul>
