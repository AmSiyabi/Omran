{{-- ربحية الدورات — بطاقات الجوال (< md). الجدول للشاشات الأوسع وPDF. --}}
@if ($cohorts->isEmpty())
    <p class="px-4 py-6 text-muted">{{ __('finance.no_data_for_period') }}</p>
@else
    <ul class="divide-y divide-line">
        @foreach ($cohorts as $row)
            <li class="space-y-2 px-4 py-4" wire:key="m-cohort-{{ $row->cohort_id }}">
                <p class="font-medium text-navy">
                    {{ $row->title_override_ar ?? $row->course_title }}
                    <span class="text-xs text-muted" dir="ltr">{{ $row->cohort_code }}</span>
                </p>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <dt class="text-muted">{{ __('finance.gross_revenue') }}</dt>
                    <dd class="text-end font-medium text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->gross_revenue_baisa) }}</dd>
                    <dt class="text-muted">{{ __('finance.direct_costs') }}</dt>
                    <dd class="text-end text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->direct_costs_baisa) }}</dd>
                    <dt class="text-muted">{{ __('finance.deliverer_share') }}</dt>
                    <dd class="text-end text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->deliverer_share_baisa) }}</dd>
                    <dt class="text-muted">{{ __('finance.center_share') }}</dt>
                    <dd class="text-end font-bold text-navy" dir="ltr">{{ \App\Support\Baisa::toString((int) $row->center_share_baisa) }}</dd>
                    <dt class="text-muted">{{ __('finance.margin') }}</dt>
                    <dd class="text-end text-navy" dir="ltr">{{ number_format($row->margin_basis_points / 100, 1) }}%</dd>
                </dl>
            </li>
        @endforeach
    </ul>
@endif
