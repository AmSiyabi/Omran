<div class="mx-auto max-w-4xl">
    <a href="{{ route('admin.cohorts.show', $cohort->id) }}" wire:navigate class="text-sm text-muted hover:text-navy">
        ← {{ $cohort->displayTitle() }}
    </a>

    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.enrollments') }}</h1>

        <div class="flex items-center gap-2">
            <span class="text-sm text-muted">{{ __('courses.seats') }}: {{ $cohort->seats_taken }}{{ $cohort->capacity ? ' / '.$cohort->capacity : '' }}</span>
            @can('export', App\Models\Enrollment::class)
                <x-button variant="secondary" size="sm" wire:click="export" wire:loading.attr="disabled" wire:target="export">
                    {{ __('courses.export_xlsx') }}
                </x-button>
            @endcan
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <x-input
            name="search"
            type="search"
            wire:model.live.debounce.400ms="search"
            :placeholder="__('courses.enrollment_search')"
        />
        <x-select name="statusFilter" wire:model.live="statusFilter">
            <option value="">{{ __('courses.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </x-select>
    </div>

    @can('manage', App\Models\Enrollment::class)
        @if (count($selected) > 0)
            <div class="sticky top-16 z-20 mt-3 flex items-center justify-between gap-3 rounded-lg border border-gold/40 bg-white p-3 shadow-sm lg:top-2">
                <span class="text-sm font-medium text-navy">{{ count($selected) }} ✓</span>
                <x-button size="sm" variant="gold" wire:click="bulkApprove" wire:loading.attr="disabled" wire:target="bulkApprove">
                    {{ __('courses.bulk_approve') }}
                </x-button>
            </div>
        @endif
    @endcan

    <x-card class="mt-4" :padding="false">
        @if ($enrollments->isEmpty())
            <x-empty-state :title="__('courses.no_enrollments')" />
        @else
            <ul class="divide-y divide-line" wire:loading.class="pointer-events-none opacity-50" wire:target="bulkApprove">
                @foreach ($enrollments as $enrollment)
                    <li class="px-4 py-3 sm:px-5" wire:key="enrollment-{{ $enrollment->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                @can('manage', App\Models\Enrollment::class)
                                    @if (in_array($enrollment->status, [App\Enums\EnrollmentStatus::Pending, App\Enums\EnrollmentStatus::Waitlisted], true))
                                        <input
                                            type="checkbox"
                                            value="{{ $enrollment->id }}"
                                            wire:model.live="selected"
                                            class="mt-1.5 size-4 rounded border-line"
                                            aria-label="{{ $enrollment->full_name_ar }}"
                                        >
                                    @endif
                                @endcan

                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-navy">{{ $enrollment->full_name_ar }}</span>
                                        <x-badge :variant="$enrollment->status->badgeVariant()">{{ $enrollment->status->label() }}</x-badge>
                                        <x-badge :variant="$enrollment->payment_status->badgeVariant()">{{ $enrollment->payment_status->label() }}</x-badge>
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-muted" dir="ltr">{{ $enrollment->email }} · {{ $enrollment->phone }}</p>
                                    <p class="mt-0.5 text-xs text-muted">
                                        @if ($enrollment->organization_ar) {{ $enrollment->organization_ar }} · @endif
                                        {{ __('courses.amount_due') }}: {{ \App\Support\Baisa::format($enrollment->amount_due_baisa) }}
                                    </p>
                                </div>
                            </div>

                            @can('manage', App\Models\Enrollment::class)
                                <div class="flex shrink-0 flex-col items-end gap-1 sm:flex-row sm:items-center">
                                    @if (in_array($enrollment->status, [App\Enums\EnrollmentStatus::Pending, App\Enums\EnrollmentStatus::Waitlisted], true))
                                        <x-button variant="ghost" size="sm" wire:click="approve({{ $enrollment->id }})" wire:loading.attr="disabled" wire:target="approve">
                                            {{ __('courses.approve') }}
                                        </x-button>
                                    @endif
                                    @if ($enrollment->status !== App\Enums\EnrollmentStatus::Cancelled)
                                        <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmCancel({{ $enrollment->id }})">
                                            {{ __('common.cancel') }}
                                        </x-button>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4">
        {{ $enrollments->links() }}
    </div>

    {{-- تأكيد الإلغاء --}}
    <x-lw-modal :show="$cancellingId !== null" close="cancelCancel" :title="$cancellingEnrollment ? __('courses.cancel_enrollment_title', ['name' => $cancellingEnrollment->full_name_ar]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('courses.cancel_enrollment_warning') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="cancelEnrollment" wire:loading.attr="disabled" wire:target="cancelEnrollment">{{ __('courses.cancel_enrollment') }}</x-button>
            <x-button variant="ghost" wire:click="cancelCancel">{{ __('common.back') }}</x-button>
        </div>
    </x-lw-modal>
</div>
