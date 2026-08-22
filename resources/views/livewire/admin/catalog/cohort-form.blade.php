<div class="mx-auto max-w-3xl pb-24">
    <h1 class="text-2xl text-navy">{{ $cohortId ? __('courses.edit_cohort') : __('courses.new_cohort') }}</h1>

    <form wire:submit="save" id="cohort-form" class="mt-6 space-y-6">
        <x-card>
            <div class="space-y-4">
                <x-select :label="__('courses.course')" name="course_id" wire:model="course_id" :placeholder="__('common.choose')" required>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title_ar }}</option>
                    @endforeach
                </x-select>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input :label="__('courses.cohort_code')" name="code" wire:model="code" :hint="__('courses.cohort_code_hint')" dir="ltr" />
                    <x-input :label="__('courses.title_override')" name="title_override_ar" wire:model="title_override_ar" />
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select :label="__('courses.delivery_mode')" name="delivery_mode" wire:model="delivery_mode" required>
                    @foreach ($modes as $mode)
                        <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                    @endforeach
                </x-select>

                <x-input :label="__('courses.city')" name="city_ar" wire:model="city_ar" />
                <x-input :label="__('courses.venue')" name="venue_ar" wire:model="venue_ar" />
                <x-input :label="__('courses.venue_url')" name="venue_url" type="url" wire:model="venue_url" :hint="__('common.optional')" />
                <x-input :label="__('courses.starts_at')" name="starts_at" type="datetime-local" wire:model="starts_at" required />
                <x-input :label="__('courses.ends_at')" name="ends_at" type="datetime-local" wire:model="ends_at" required />
            </div>
        </x-card>

        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input :label="__('courses.capacity')" name="capacity" type="number" inputmode="numeric" wire:model="capacity" :hint="__('courses.capacity_hint')" />

                <div>
                    <x-input :label="__('courses.price')" name="price" type="text" inputmode="decimal" wire:model="price" :hint="__('courses.price_hint')" :disabled="$is_free" />
                    <label class="mt-2 flex min-h-11 items-center gap-2 text-sm text-navy">
                        <input type="checkbox" wire:model.live="is_free" class="size-4 rounded border-line">
                        {{ __('courses.is_free') }}
                    </label>
                </div>

                <x-select :label="__('courses.client')" name="client_id" wire:model="client_id">
                    <option value="">{{ __('courses.no_client') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name_ar }}</option>
                    @endforeach
                </x-select>

                <x-select :label="__('courses.invoicing_entity')" name="invoicing_entity_id" wire:model="invoicing_entity_id" required>
                    @foreach ($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->name_ar }}</option>
                    @endforeach
                </x-select>

                <x-input :label="__('courses.registration_opens_at')" name="registration_opens_at" type="datetime-local" wire:model="registration_opens_at" :hint="__('common.optional')" />
                <x-input :label="__('courses.registration_closes_at')" name="registration_closes_at" type="datetime-local" wire:model="registration_closes_at" :hint="__('common.optional')" />
            </div>
        </x-card>

        <x-card>
            <x-textarea :label="__('courses.internal_notes')" name="internal_notes" wire:model="internal_notes" rows="3" :hint="__('common.optional')" />
        </x-card>
    </form>

    <div class="fixed inset-x-0 bottom-16 z-30 border-t border-line bg-cream/95 px-4 py-3 backdrop-blur lg:bottom-0 lg:ms-64">
        <div class="mx-auto flex max-w-3xl flex-row-reverse items-center justify-start gap-2">
            <x-button type="submit" form="cohort-form" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
            <x-button variant="ghost" :href="route('admin.cohorts')" wire:navigate>{{ __('common.cancel') }}</x-button>
            <span
                wire:dirty
                data-dirty-marker
                data-confirm="{{ __('common.leave_unsaved_confirm') }}"
                class="me-auto text-xs font-medium text-warning"
            >{{ __('common.unsaved_changes') }}</span>
        </div>
    </div>
</div>
