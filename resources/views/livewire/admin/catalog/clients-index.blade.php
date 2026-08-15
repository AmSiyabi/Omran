<div>
    <x-catalog-subnav current="clients" />

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl text-navy">{{ __('courses.clients') }}</h1>

        @can('create', App\Models\Client::class)
            <x-button size="sm" variant="gold" wire:click="create">{{ __('courses.new_client') }}</x-button>
        @endcan
    </div>

    <div class="mt-4">
        <x-input
            name="search"
            type="search"
            wire:model.live.debounce.400ms="search"
            :placeholder="__('common.search')"
        />
    </div>

    <x-card class="mt-4" :padding="false">
        @if ($clients->isEmpty())
            <x-empty-state
                :title="__('courses.no_clients_title')"
                :description="__('courses.no_clients_description')"
            />
        @else
            <ul class="divide-y divide-line">
                @foreach ($clients as $client)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5" wire:key="client-{{ $client->id }}">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-navy">{{ $client->name_ar }}</p>
                            <p class="truncate text-xs text-muted">
                                {{ $client->type->label() }}
                                @if ($client->contact_name)
                                    · {{ $client->contact_name }}
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            @can('update', $client)
                                <x-button variant="ghost" size="sm" wire:click="edit({{ $client->id }})">{{ __('common.edit') }}</x-button>
                            @endcan
                            @can('delete', $client)
                                <x-button variant="ghost" size="sm" class="text-error" wire:click="confirmDelete({{ $client->id }})">{{ __('common.delete') }}</x-button>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-4">
        {{ $clients->links() }}
    </div>

    <x-lw-modal :show="$showForm" close="closeForm" :title="$editingId ? __('courses.edit_client') : __('courses.new_client')" maxWidth="lg">
        <form wire:submit="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input :label="__('courses.client_name')" name="name_ar" wire:model="name_ar" required />
                <x-input :label="__('courses.title_en')" name="name_en" wire:model="name_en" />

                <x-select :label="__('courses.client_type')" name="type" wire:model="type" required>
                    @foreach ($clientTypes as $clientType)
                        <option value="{{ $clientType->value }}">{{ $clientType->label() }}</option>
                    @endforeach
                </x-select>

                <x-input :label="__('courses.contact_name')" name="contact_name" wire:model="contact_name" />
                <x-input :label="__('courses.contact_email')" name="contact_email" type="email" wire:model="contact_email" />
                <x-input :label="__('courses.contact_phone')" name="contact_phone" type="tel" wire:model="contact_phone" />
                <x-input :label="__('courses.cr_number')" name="cr_number" wire:model="cr_number" />
                <x-input :label="__('courses.vat_number')" name="vat_number" wire:model="vat_number" />
            </div>

            <x-textarea :label="__('courses.address')" name="address_ar" wire:model="address_ar" rows="2" :hint="__('common.optional')" />
            <x-textarea :label="__('courses.internal_notes')" name="notes" wire:model="notes" rows="2" :hint="__('common.optional')" />

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-button type="submit" target="save" :loading-label="__('common.saving')">{{ __('common.save') }}</x-button>
                <x-button variant="ghost" type="button" wire:click="closeForm">{{ __('common.cancel') }}</x-button>
            </div>
        </form>
    </x-lw-modal>

    <x-lw-modal :show="$deletingId !== null" close="cancelDelete" :title="$deletingClient ? __('courses.delete_client_title', ['name' => $deletingClient->name_ar]) : ''" maxWidth="sm">
        <p class="text-sm text-muted">{{ __('common.are_you_sure') }} {{ __('common.action_irreversible') }}.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
            <x-button variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">{{ __('common.delete') }}</x-button>
            <x-button variant="ghost" wire:click="cancelDelete">{{ __('common.cancel') }}</x-button>
        </div>
    </x-lw-modal>
</div>
