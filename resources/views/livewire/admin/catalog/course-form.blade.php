<div class="mx-auto max-w-3xl pb-24">
    <h1 class="text-2xl text-navy">{{ $courseId ? __('courses.edit_course') : __('courses.new_course') }}</h1>

    <form wire:submit="save" id="course-form" class="mt-6 space-y-6">
        <x-card>
            <div class="space-y-4">
                <x-input :label="__('courses.title_ar')" name="title_ar" wire:model="title_ar" required />
                <x-input :label="__('courses.title_en')" name="title_en" wire:model="title_en" :hint="__('common.optional')" />
                <x-textarea :label="__('courses.summary_ar')" name="summary_ar" wire:model="summary_ar" rows="2" required />
                <x-textarea :label="__('courses.description_ar')" name="description_ar" wire:model="description_ar" rows="6" required />

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-select :label="__('courses.category')" name="category_id" wire:model="category_id" :placeholder="__('common.choose')" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name_ar }}</option>
                        @endforeach
                    </x-select>

                    <x-select :label="__('courses.level')" name="level" wire:model="level" required>
                        @foreach ($levels as $courseLevel)
                            <option value="{{ $courseLevel->value }}">{{ $courseLevel->label() }}</option>
                        @endforeach
                    </x-select>

                    <x-input :label="__('courses.duration_hours')" name="duration_hours" type="text" inputmode="decimal" wire:model="duration_hours" required />
                </div>
            </div>
        </x-card>

        {{-- مخرجات التعلم --}}
        <x-card>
            <h2 class="font-bold text-navy">{{ __('courses.outcomes_ar') }}</h2>
            @error('outcomes_ar')
                <p class="mt-1 text-sm text-error">{{ $message }}</p>
            @enderror

            <div class="mt-3 space-y-2">
                @foreach ($outcomes_ar as $index => $outcome)
                    <div class="flex items-start gap-2" wire:key="outcome-{{ $index }}">
                        <div class="flex-1">
                            <x-input :name="'outcomes_ar.'.$index" wire:model="outcomes_ar.{{ $index }}" />
                        </div>
                        <button
                            type="button"
                            class="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted transition hover:bg-error/10 hover:text-error"
                            wire:click="removeOutcome({{ $index }})"
                            aria-label="{{ __('common.delete') }}"
                        >
                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <x-button type="button" variant="secondary" size="sm" wire:click="addOutcome">{{ __('courses.add_outcome') }}</x-button>
            </div>
        </x-card>

        <x-card>
            <div class="space-y-4">
                <x-textarea :label="__('courses.target_audience_ar')" name="target_audience_ar" wire:model="target_audience_ar" rows="2" :hint="__('common.optional')" />
                <x-textarea :label="__('courses.prerequisites_ar')" name="prerequisites_ar" wire:model="prerequisites_ar" rows="2" :hint="__('common.optional')" />
            </div>
        </x-card>

        {{-- صورة الغلاف --}}
        <x-card>
            <h2 class="font-bold text-navy">{{ __('courses.cover_image') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('courses.cover_image_hint') }}</p>

            <div class="mt-3 flex items-center gap-4">
                @if ($cover)
                    <img src="{{ $cover->temporaryUrl() }}" alt="" class="size-20 rounded-lg border border-line object-cover">
                @elseif ($existingCover)
                    <img src="{{ $existingCover }}" alt="" class="size-20 rounded-lg border border-line object-cover">
                @endif

                <div class="flex-1">
                    <input
                        type="file"
                        id="cover"
                        wire:model="cover"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        class="block w-full text-sm text-muted file:me-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0 file:bg-navy file:px-4 file:text-sm file:font-medium file:text-cream-warm hover:file:bg-navy-ink"
                    >
                    <div wire:loading wire:target="cover" class="mt-2 flex items-center gap-2 text-sm text-muted">
                        <x-spinner class="size-4" />
                        {{ __('common.loading') }}
                    </div>
                    @error('cover') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card>

        {{-- SEO --}}
        <x-card>
            <div class="space-y-4">
                <x-input :label="__('courses.meta_title_ar')" name="meta_title_ar" wire:model="meta_title_ar" :hint="__('common.optional')" />
                <x-input :label="__('courses.meta_description_ar')" name="meta_description_ar" wire:model="meta_description_ar" :hint="__('common.optional')" />
            </div>
        </x-card>
    </form>

    {{-- شريط الحفظ الثابت — القانون: زر الحفظ ظاهر دائماً --}}
    <div class="fixed inset-x-0 bottom-16 z-30 border-t border-line bg-cream/95 px-4 py-3 backdrop-blur lg:bottom-0 lg:ms-64">
        <div class="mx-auto flex max-w-3xl flex-row-reverse items-center justify-start gap-2">
            <x-button type="submit" form="course-form" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
            <x-button variant="ghost" :href="route('admin.courses')" wire:navigate>{{ __('common.cancel') }}</x-button>
        </div>
    </div>
</div>
