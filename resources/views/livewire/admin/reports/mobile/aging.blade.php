{{-- أعمار الذمم — بطاقات الجوال (< md) --}}
@if ($aging['rows'] === [])
    <p class="px-4 py-6 text-muted">{{ __('finance.no_data_for_period') }}</p>
@else
    <ul class="divide-y divide-line">
        @foreach ($aging['rows'] as $row)
            <li class="space-y-2 px-4 py-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 font-medium text-navy">{{ $row['label'] }}</p>
                    <p class="shrink-0 font-bold text-navy" dir="ltr">{{ $row['total']->toDecimalString() }}</p>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    @foreach (['0_30', '31_60', '61_90', '90_plus'] as $bucket)
                        @unless ($row['buckets'][$bucket]->isZero())
                            <dt class="text-muted">{{ __('finance.aging_'.$bucket) }}</dt>
                            <dd class="text-end {{ $bucket === '90_plus' ? 'font-bold text-error' : 'text-navy' }}" dir="ltr">
                                {{ $row['buckets'][$bucket]->toDecimalString() }}
                            </dd>
                        @endunless
                    @endforeach
                </dl>
            </li>
        @endforeach
    </ul>

    <div class="flex items-center justify-between border-t border-line px-4 py-3">
        <span class="font-bold text-navy">الإجمالي</span>
        <span class="font-bold text-navy" dir="ltr">{{ $aging['grand_total']->format() }}</span>
    </div>
@endif
