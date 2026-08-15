<div>
    <x-catalog-subnav current="instructors" />

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.instructors') }}</h1>

        @can('create', App\Models\Instructor::class)
            <x-button size="sm" variant="gold" wire:click="create">{{ __('courses.new_instructor') }}</x-button>
        @endcan
    </div>

    <div class="mt-4">
        <x-input
            name="search"
            type="search"
            :label="null"
            wire:model.live.debounce.400ms="search"
            :placeholder="__('common.search')"
        />
    </div>

    <x-card class="mt-4" :padding="false">
        @if ($instructors->isEmpty())
            <x-empty-state
                :title="__('courses.no_instructors_title')"
                :description="__('courses.no_instructors_description')"
            />
        @else
            <ul class="divide-y divide-line">
                @foreach ($instructors as $instructor)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="instructor-{{ $instructor->id }}">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-navy">{{ $instructor->name_ar }}</p>
                            <p class="truncate text-xs text-muted">
                                {{ $instructor->specialization_ar ?? '—' }}
                                @unless ($instructor->is_public)
                                    · {{ __('courses.inactive') }}
                                @endunless
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            @can('update', $instructor)
                                <x-button variant="ghost" size="sm" wire:click="edit({{ $instructor->id }})">{{ __('common.edit') }}</x-button>
                            @endcan
                            @can('delete', $instructor)
                                <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmDelete({{ $instructor->id }})">{{ __('common.delete') }}</x-button>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4">
        {{ $instructors->links() }}
    </div>

    <x-lw-modal :show="$showForm" close="closeForm" :title="$editingId ? __('courses.edit_instructor') : __('courses.new_instructor')" maxWidth="lg">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input :label="__('courses.instructor_name')" name="name_ar" wire:model="name_ar" required />
                <x-input :label="__('courses.title_en')" name="name_en" wire:model="name_en" />
                <x-input :label="__('courses.specialization')" name="specialization_ar" wire:model="specialization_ar" />
                <x-input :label="__('auth.email')" name="email" type="email" wire:model="email" />
                <x-input :label="__('courses.contact_phone')" name="phone" type="tel" wire:model="phone" />
            </div>

            <x-textarea :label="__('courses.bio')" name="bio_ar" wire:model="bio_ar" rows="3" />
            <x-textarea :label="__('courses.internal_notes')" name="notes" wire:model="notes" rows="2" :hint="__('common.optional')" />

            <label class="flex min-h-11 items-center gap-2 text-sm text-navy">
                <input type="checkbox" wire:model="is_public" class="size-4 rounded border-line">
                {{ __('courses.is_public_profile') }}
            </label>

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>

    <x-lw-modal :show="$deletingId !== null" close="cancelDelete" :title="$deletingInstructor ? __('courses.delete_instructor_title', ['name' => $deletingInstructor->name_ar]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('common.are_you_sure') }} {{ __('common.action_irreversible') }}.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">{{ __('common.delete') }}</x-button>
            <x-button variant="ghost" wire:click="cancelDelete">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
