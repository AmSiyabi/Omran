<div>
    <x-catalog-subnav current="courses" />

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.courses') }}</h1>

        @can('create', App\Models\Course::class)
            <x-button size="sm" variant="gold" :href="route('admin.courses.create')" wire:navigate>{{ __('courses.new_course') }}</x-button>
        @endcan
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <x-input
            name="search"
            type="search"
            wire:model.live.debounce.400ms="search"
            :placeholder="__('courses.search_courses')"
        />
        <x-select name="categoryFilter" wire:model.live="categoryFilter" aria-label="{{ __('courses.filter_by_category') }}">
            <option value="">{{ __('courses.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name_ar }}</option>
            @endforeach
        </x-select>
        <x-select name="statusFilter" wire:model.live="statusFilter" aria-label="{{ __('courses.filter_by_status') }}">
            <option value="">{{ __('courses.all_statuses') }}</option>
            <option value="published">{{ __('courses.published') }}</option>
            <option value="draft">{{ __('courses.draft') }}</option>
        </x-select>
    </div>

    @if ($courses->isEmpty())
        <x-card class="mt-4" :padding="false">
            <x-empty-state
                :title="__('courses.no_courses_title')"
                :description="__('courses.no_courses_description')">
                @can('create', App\Models\Course::class)
                    <x-slot:action>
                        <x-button variant="gold" :href="route('admin.courses.create')" wire:navigate>{{ __('courses.new_course') }}</x-button>
                    </x-slot:action>
                @endcan
            </x-empty-state>
        </x-card>
    @else
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <x-card wire:key="course-{{ $course->id }}" class="flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <svg class="size-2.5 shrink-0" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="{{ $course->category->accent_color ?? '#cda34f' }}" /></svg>
                            <span class="truncate text-xs text-muted">{{ $course->category->name_ar }}</span>
                        </div>
                        <x-badge :variant="$course->is_published ? 'success' : 'neutral'">
                            {{ $course->is_published ? __('courses.published') : __('courses.draft') }}
                        </x-badge>
                    </div>

                    <h2 class="mt-2 font-bold text-navy">{{ $course->title_ar }}</h2>
                    <p class="mt-1 line-clamp-2 flex-1 text-sm text-muted">{{ $course->summary_ar }}</p>

                    <p class="mt-3 text-xs text-muted">
                        {{ $course->level->label() }}
                        · {{ rtrim(rtrim((string) $course->duration_hours, '0'), '.') }} {{ __('courses.hours_short') }}
                        · {{ __('courses.cohorts_count') }}: {{ $course->cohorts_count }}
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-1 border-t border-line pt-3">
                        @can('update', $course)
                            <x-button variant="ghost" size="sm" :href="route('admin.courses.edit', $course)" wire:navigate>{{ __('common.edit') }}</x-button>
                        @endcan
                        @can('publish', $course)
                            <x-button variant="ghost" size="sm" wire:click="togglePublish({{ $course->id }})" wire:loading.attr="disabled" wire:target="togglePublish({{ $course->id }})">
                                {{-- تفاؤلي: التسمية المعاكسة تظهر فور النقر --}}
                                <span wire:loading.remove wire:target="togglePublish({{ $course->id }})">
                                    {{ $course->is_published ? __('courses.unpublish') : __('courses.publish') }}
                                </span>
                                <span wire:loading wire:target="togglePublish({{ $course->id }})">
                                    {{ $course->is_published ? __('courses.publish') : __('courses.unpublish') }}
                                </span>
                            </x-button>
                        @endcan
                        @can('delete', $course)
                            <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmDelete({{ $course->id }})">{{ __('common.delete') }}</x-button>
                        @endcan
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    <div class="mt-4">
        {{ $courses->links() }}
    </div>

    <x-lw-modal :show="$deletingId !== null" close="cancelDelete" :title="$deletingCourse ? __('courses.delete_course_title', ['title' => $deletingCourse->title_ar]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('courses.delete_course_warning') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">{{ __('common.delete') }}</x-button>
            <x-button variant="ghost" wire:click="cancelDelete">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
