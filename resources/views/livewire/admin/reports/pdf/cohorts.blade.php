@if ($cohorts->isEmpty())
    <p class="px-4 py-6 text-muted">{{ __('finance.no_data_for_period') }}</p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.cohort') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.gross_revenue') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.direct_costs') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.deliverer_share') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.center_share') }}</th>
                <th class="bg-navy px-3 py-2 text-start text-cream-warm">{{ __('finance.margin') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cohorts as $row)
                <tr>
                    <td class="border-b border-line px-3 py-2 text-navy">
                        {{ $row->title_override_ar ?? $row->course_title }}
                        <span class="text-xs text-muted" dir="ltr">{{ $row->cohort_code }}</span>
                    </td>
                    <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->gross_revenue_baisa) }}</td>
                    <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->direct_costs_baisa) }}</td>
                    <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->deliverer_share_baisa) }}</td>
                    <td class="num border-b border-line px-3 py-2 font-medium text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->center_share_baisa) }}</td>
                    <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ number_format($row->margin_basis_points / 100, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
