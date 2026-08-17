<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.cohorts.show', $cohort->id) }}" wire:navigate class="text-sm text-muted hover:text-navy">
        ← {{ $cohort->displayTitle() }}
    </a>

    <h1 class="mt-2 text-2xl text-navy">{{ __('courses.attendance_sheet') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('courses.attendance_hint') }}</p>

    @if ($cohort->sessions->isEmpty())
        <x-card class="mt-5" :padding="false">
            <x-empty-state :title="__('courses.no_sessions')" />
        </x-card>
    @else
        {{-- اختيار الجلسة --}}
        <nav class="scrollbar-none -mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('courses.select_session') }}">
            @foreach ($cohort->sessions as $session)
                <button
                    type="button"
                    wire:click="selectSession({{ $session->id }})"
                    wire:key="session-chip-{{ $session->id }}"
                    @class([
                        'inline-flex min-h-10 shrink-0 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                        'border-navy bg-navy text-cream-warm' => $sessionId === $session->id,
                        'border-line bg-white text-navy hover:bg-navy/5' => $sessionId !== $session->id,
                    ])
                >
                    {{ $session->session_number }}. {{ $session->title_ar ?? $session->starts_at->timezone(config('app.display_timezone'))->format('m-d') }}
                </button>
            @endforeach
        </nav>

        @if ($enrollments->isEmpty())
            <x-card class="mt-4" :padding="false">
                <x-empty-state :title="__('courses.no_participants')" />
            </x-card>
        @else
            <div class="mt-4 flex justify-end">
                <x-button variant="secondary" size="sm" wire:click="markAllPresent" wire:loading.attr="disabled" wire:target="markAllPresent">
                    {{ __('courses.mark_all_present') }}
                </x-button>
            </div>

            <x-card class="mt-3" :padding="false">
                <ul class="divide-y divide-line">
                    @foreach ($enrollments as $enrollment)
                        @php $record = $records->get($enrollment->id); @endphp
                        <li wire:key="attendance-{{ $enrollment->id }}">
                            <button
                                type="button"
                                wire:click="toggle({{ $enrollment->id }})"
                                class="flex min-h-14 w-full items-center justify-between gap-3 px-4 py-2 text-start transition-colors hover:bg-navy/[0.03] sm:px-5"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-navy">{{ $enrollment->full_name_ar }}</span>
                                    @if ($enrollment->organization_ar)
                                        <span class="block truncate text-xs text-muted">{{ $enrollment->organization_ar }}</span>
                                    @endif
                                </span>

                                @if ($record === null)
                                    <x-badge variant="neutral">—</x-badge>
                                @else
                                    <x-badge :variant="match ($record->status) {
                                        App\Enums\AttendanceStatus::Present => 'success',
                                        App\Enums\AttendanceStatus::Absent => 'error',
                                        App\Enums\AttendanceStatus::Late => 'warning',
                                        App\Enums\AttendanceStatus::Excused => 'info',
                                    }">
                                        {{ $record->status->label() }}
                                    </x-badge>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    @endif
</div>
