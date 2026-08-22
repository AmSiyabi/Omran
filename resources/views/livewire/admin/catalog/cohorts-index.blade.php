<div>
    <x-catalog-subnav current="cohorts" />

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.cohorts') }}</h1>

        @can('create', App\Models\Cohort::class)
            <x-button size="sm" variant="gold" :href="route('admin.cohorts.create')" wire:navigate>{{ __('courses.new_cohort') }}</x-button>
        @endcan
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <x-input
            name="search"
            type="search"
            wire:model.live.debounce.400ms="search"
            :placeholder="__('courses.search_cohorts')"
        />
        <x-select name="statusFilter" wire:model.live="statusFilter" aria-label="{{ __('courses.filter_by_status') }}">
            <option value="">{{ __('courses.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </x-select>
    </div>

    @if ($cohorts->isEmpty())
        <x-card class="mt-4" :padding="false">
            <x-empty-state
                :title="__('courses.no_cohorts_title')"
                :description="__('courses.no_cohorts_description')">
                @can('create', App\Models\Cohort::class)
                    <x-slot:action>
                        <x-button variant="gold" :href="route('admin.cohorts.create')" wire:navigate>{{ __('courses.new_cohort') }}</x-button>
                    </x-slot:action>
                @endcan
            </x-empty-state>
        </x-card>
    @else
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cohorts as $cohort)
                <a href="{{ route('admin.cohorts.show', $cohort) }}" wire:navigate wire:key="cohort-{{ $cohort->id }}" class="block rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold">
                    <x-card class="h-full transition-shadow hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-mono text-xs text-muted" dir="ltr">{{ $cohort->code }}</span>
                            <x-badge :variant="$cohort->status->badgeVariant()">{{ $cohort->status->label() }}</x-badge>
                        </div>

                        <h2 class="mt-2 font-bold text-navy">{{ $cohort->title_override_ar ?? $cohort->course->title_ar }}</h2>

                        <p class="mt-2 text-xs text-muted">
                            {{ $cohort->starts_at->timezone(config('app.display_timezone'))->format('Y-m-d') }}
                            @if ($cohort->client)
                                · {{ $cohort->client->name_ar }}
                            @endif
                        </p>

                        <p class="mt-1 text-sm font-medium text-navy">
                            @if ($cohort->is_free)
                                {{ __('courses.is_free') }}
                            @else
                                {{ $baisa::format($cohort->price_baisa) }}
                            @endif
                            @if ($cohort->capacity)
                                <span class="text-xs font-normal text-muted">· {{ $cohort->seats_taken }}/{{ $cohort->capacity }}</span>
                            @endif
                        </p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-4">
        {{ $cohorts->links() }}
    </div>
</div>
