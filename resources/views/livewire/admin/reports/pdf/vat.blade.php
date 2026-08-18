@php
    $percent = min(100, $vat['percent_of_mandatory']);
    $barColor = match ($vat['state']) {
        'green' => '#2e7d5b',
        'amber' => '#cda34f',
        'orange' => '#a06d1e',
        default => '#a8433e',
    };
@endphp

<div class="space-y-4 p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-bold text-navy">{{ __('finance.vat_gauge_title') }}</h2>
        <span class="font-bold text-navy" dir="ltr">{{ $vat['taxable']->toDecimalString() }} / {{ $vat['mandatory']->toDecimalString() }} {{ __('common.omr') }} ({{ $vat['percent_of_mandatory'] }}%)</span>
    </div>

    <svg class="h-3 w-full overflow-hidden rounded-full" preserveAspectRatio="none" viewBox="0 0 100 4">
        <rect width="100" height="4" fill="#e4dcc9" />
        <rect width="{{ $percent }}" height="4" fill="{{ $barColor }}" />
    </svg>

    <p class="rounded-lg p-3 text-sm {{ $vat['state'] === 'green' ? 'bg-success-soft text-success' : ($vat['state'] === 'red' ? 'bg-error-soft text-error' : 'bg-warning-soft text-warning') }}">
        {{ __('finance.vat_state_'.$vat['state']) }}
    </p>

    <table class="w-full text-sm">
        <tbody>
            <tr>
                <td class="border-b border-line px-3 py-2 text-muted">حد التسجيل الاختياري</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $vat['voluntary']->format() }}</td>
            </tr>
            <tr>
                <td class="border-b border-line px-3 py-2 text-muted">{{ __('finance.tax_mandatory_threshold') }}</td>
                <td class="num border-b border-line px-3 py-2 text-navy" dir="ltr">{{ $vat['mandatory']->format() }}</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-muted">{{ __('finance.tax_remaining_to_threshold') }}</td>
                <td class="num px-3 py-2 font-bold text-navy" dir="ltr">{{ \App\Support\Baisa::format(max(0, $vat['mandatory']->baisa - $vat['taxable']->baisa)) }}</td>
            </tr>
        </tbody>
    </table>
</div>
