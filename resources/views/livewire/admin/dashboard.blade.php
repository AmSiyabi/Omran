<div>
    <h1 class="text-2xl text-navy">{{ __('common.welcome') }}، {{ $user->name_ar }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('common.center_name') }}</p>

    @if ($metrics === null)
        {{-- دون صلاحية مالية: بطاقات تشغيلية فقط --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <x-card>
                <h2 class="text-base font-bold text-navy">{{ __('common.nav_courses') }}</h2>
                <p class="mt-2 text-sm text-muted">{{ __('common.dashboard_courses_placeholder') }}</p>
                <div class="mt-4">
                    <x-button variant="secondary" size="sm" :href="route('admin.courses')" wire:navigate>{{ __('common.nav_courses') }}</x-button>
                </div>
            </x-card>
            <x-card>
                <h2 class="text-base font-bold text-navy">{{ __('auth.security_title') }}</h2>
                <div class="mt-4">
                    <x-button variant="secondary" size="sm" :href="route('admin.security')" wire:navigate>{{ __('common.manage') }}</x-button>
                </div>
            </x-card>
        </div>
    @else
        {{-- الوضع النقدي --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <x-card :padding="false" class="p-4">
                <p class="text-xs text-muted">{{ __('finance.dashboard_cash') }}</p>
                <p class="mt-1 text-lg font-bold text-navy">{{ $metrics['cash_total']->format() }}</p>
                <p class="mt-1 text-[11px] leading-4 text-muted">
                    {{ __('finance.dashboard_bank') }}: {{ $metrics['cash']['1020']->toDecimalString() }}<br>
                    {{ __('finance.dashboard_cash_box') }}: {{ $metrics['cash']['1010']->toDecimalString() }}
                </p>
            </x-card>
            <x-card :padding="false" class="p-4">
                <p class="text-xs text-muted">{{ __('finance.dashboard_mtd_revenue') }}</p>
                <p class="mt-1 text-lg font-bold text-navy">{{ $metrics['mtd_revenue']->format() }}</p>
            </x-card>
            <x-card :padding="false" class="p-4">
                <p class="text-xs text-muted">{{ __('finance.dashboard_receivables') }}</p>
                <p class="mt-1 text-lg font-bold {{ $metrics['receivables']->isPositive() ? 'text-warning' : 'text-navy' }}">{{ $metrics['receivables']->format() }}</p>
            </x-card>
            <x-card :padding="false" class="p-4">
                <p class="text-xs text-muted">{{ __('finance.dashboard_partners') }}</p>
                @foreach ($metrics['partner_balances'] as $row)
                    <p class="mt-1 flex justify-between text-sm">
                        <span class="text-navy">{{ $row['partner']->display_name_ar }}</span>
                        <span class="font-bold text-navy">{{ $row['balance']->toDecimalString() }}</span>
                    </p>
                @endforeach
            </x-card>
        </div>

        {{-- مؤشر القيمة المضافة (spec §8.9) --}}
        @php
            $vat = $metrics['vat'];
            $vatPercent = min(100, $vat['percent_of_mandatory']);
            $vatColor = match ($vat['state']) {
                'green' => '#2e7d5b',
                'amber' => '#cda34f',
                'orange' => '#a06d1e',
                default => '#a8433e',
            };
        @endphp
        <x-card class="mt-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-navy">{{ __('finance.vat_gauge_title') }}</h2>
                <span class="text-sm font-bold text-navy" dir="ltr">
                    {{ $vat['taxable']->toDecimalString() }} / {{ $vat['mandatory']->toDecimalString() }} {{ __('common.omr') }}
                </span>
            </div>
            <svg class="mt-3 h-2.5 w-full overflow-hidden rounded-full" preserveAspectRatio="none" viewBox="0 0 100 4" aria-hidden="true">
                <rect width="100" height="4" fill="#e4dcc9" />
                <rect width="{{ $vatPercent }}" height="4" fill="{{ $vatColor }}" />
            </svg>
            <p class="mt-2 text-xs text-muted">{{ __('finance.vat_state_'.$vat['state']) }}</p>
            @can('reports.tax')
                <a href="{{ route('admin.reports.tax') }}" wire:navigate class="mt-2 inline-block text-xs font-medium text-info hover:underline">{{ __('finance.tax_estimate_title') }} ←</a>
            @endcan
        </x-card>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            {{-- الدفعات القادمة --}}
            <x-card :padding="false">
                <x-slot:header><h2 class="font-bold text-navy">{{ __('finance.dashboard_upcoming') }}</h2></x-slot:header>
                @if ($metrics['upcoming_cohorts']->isEmpty())
                    <x-empty-state :title="__('courses.no_cohorts_title')" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($metrics['upcoming_cohorts'] as $cohort)
                            <li wire:key="up-{{ $cohort->id }}">
                                <a href="{{ route('admin.cohorts.show', $cohort->id) }}" wire:navigate class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-navy/[0.03]">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-navy">{{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</span>
                                        <span class="text-xs text-muted">{{ $cohort->starts_at->timezone(config('app.display_timezone'))->translatedFormat('j F') }} · {{ $cohort->seats_taken }}{{ $cohort->capacity ? '/'.$cohort->capacity : '' }}</span>
                                    </span>
                                    <x-badge :variant="$cohort->status->badgeVariant()">{{ $cohort->status->label() }}</x-badge>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- اختصارات --}}
            <x-card>
                <h2 class="font-bold text-navy">{{ __('finance.dashboard_shortcuts') }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-button variant="secondary" size="sm" :href="route('admin.finance')" wire:navigate>{{ __('finance.finance') }}</x-button>
                    @can('reports.view')
                        <x-button variant="secondary" size="sm" :href="route('admin.reports')" wire:navigate>{{ __('finance.reports') }}</x-button>
                    @endcan
                    @can('finance.settle')
                        <x-button variant="secondary" size="sm" :href="route('admin.finance.settlements')" wire:navigate>{{ __('finance.settlements') }}</x-button>
                    @endcan
                </div>
            </x-card>
        </div>
    @endif
</div>
