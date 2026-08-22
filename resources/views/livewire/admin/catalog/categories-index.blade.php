<div>
    <x-catalog-subnav current="categories" />

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.categories') }}</h1>

        @can('create', App\Models\Category::class)
            <x-button size="sm" variant="gold" wire:click="create">{{ __('courses.new_category') }}</x-button>
        @endcan
    </div>

    <x-card class="mt-6" :padding="false">
        @if ($categories->isEmpty())
            <x-empty-state
                :title="__('courses.no_categories_title')"
                :description="__('courses.no_categories_description')"
            />
        @else
            <ul class="divide-y divide-line">
                @foreach ($categories as $category)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="category-{{ $category->id }}">
                        <div class="flex min-w-0 items-center gap-3">
                            {{-- fill attribute بدل style= — سياسة CSP تمنع الأنماط المضمنة --}}
                            <svg class="size-3 shrink-0" viewBox="0 0 12 12" aria-hidden="true"><circle cx="6" cy="6" r="6" fill="{{ $category->accent_color ?? '#cda34f' }}" /></svg>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-navy">{{ $category->name_ar }}</p>
                                <p class="text-xs text-muted">{{ __('courses.courses') }}: {{ $category->courses_count }} · {{ __('courses.sort_order') }}: {{ $category->sort_order }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            @can('update', $category)
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $category->id }})"
                                    class="me-1"
                                    aria-label="{{ $category->is_active ? __('courses.active') : __('courses.inactive') }}"
                                >
                                    {{-- تفاؤلي: الحالة المعاكسة تظهر فور النقر --}}
                                    <span wire:loading.remove wire:target="toggleActive({{ $category->id }})">
                                        <x-badge :variant="$category->is_active ? 'success' : 'neutral'">
                                            {{ $category->is_active ? __('courses.active') : __('courses.inactive') }}
                                        </x-badge>
                                    </span>
                                    <span wire:loading wire:target="toggleActive({{ $category->id }})">
                                        <x-badge :variant="$category->is_active ? 'neutral' : 'success'">
                                            {{ $category->is_active ? __('courses.inactive') : __('courses.active') }}
                                        </x-badge>
                                    </span>
                                </button>

                                <x-button variant="ghost" size="sm" wire:click="edit({{ $category->id }})">{{ __('common.edit') }}</x-button>
                            @endcan

                            @can('delete', $category)
                                <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmDelete({{ $category->id }})">{{ __('common.delete') }}</x-button>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    {{-- نموذج الإنشاء/التعديل --}}
    <x-lw-modal :show="$showForm" close="closeForm" :title="$editingId ? __('courses.edit_category') : __('courses.new_category')">
        <form wire:submit="save" class="space-y-4">
            <x-input :label="__('courses.category_name')" name="name_ar" wire:model="name_ar" required />
            <x-input :label="__('courses.title_en')" name="name_en" wire:model="name_en" />
            <x-textarea :label="__('common.optional').' — '.__('courses.category_name')" name="description_ar" wire:model="description_ar" rows="2" />

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="accent_color" class="block text-sm font-medium text-navy">{{ __('courses.accent_color') }}</label>
                    <input id="accent_color" type="color" wire:model="accent_color" class="h-11 w-full cursor-pointer rounded-lg border border-line bg-white p-1">
                    @error('accent_color') <p class="text-sm text-error">{{ $message }}</p> @enderror
                </div>
                <x-input :label="__('courses.sort_order')" name="sort_order" type="number" inputmode="numeric" wire:model="sort_order" />
            </div>

            <label class="flex min-h-11 items-center gap-2 text-sm text-navy">
                <input type="checkbox" wire:model="is_active" class="size-4 rounded border-line">
                {{ __('courses.active') }}
            </label>

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>

    {{-- تأكيد الحذف --}}
    <x-lw-modal :show="$deletingId !== null" close="cancelDelete" :title="$deletingCategory ? __('courses.delete_category_title', ['name' => $deletingCategory->name_ar]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('common.are_you_sure') }} {{ __('common.action_irreversible') }}.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">{{ __('common.delete') }}</x-button>
            <x-button variant="ghost" wire:click="cancelDelete">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
