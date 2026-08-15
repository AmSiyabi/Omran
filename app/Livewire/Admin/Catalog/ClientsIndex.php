<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ClientsIndex extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public bool $showForm = false;

    #[Url(as: 'q')]
    public string $search = '';

    public string $name_ar = '';

    public string $name_en = '';

    public string $type = 'government';

    public string $contact_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $cr_number = '';

    public string $vat_number = '';

    public string $address_ar = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Client::class);

        $this->reset('editingId', 'name_ar', 'name_en', 'contact_name', 'contact_email', 'contact_phone', 'cr_number', 'vat_number', 'address_ar', 'notes');
        $this->type = 'government';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('update', $client);

        $this->editingId = $client->id;
        $this->name_ar = $client->name_ar;
        $this->name_en = (string) $client->name_en;
        $this->type = $client->type->value;
        $this->contact_name = (string) $client->contact_name;
        $this->contact_email = (string) $client->contact_email;
        $this->contact_phone = (string) $client->contact_phone;
        $this->cr_number = (string) $client->cr_number;
        $this->vat_number = (string) $client->vat_number;
        $this->address_ar = (string) $client->address_ar;
        $this->notes = (string) $client->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ClientType::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'cr_number' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'address_ar' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (['name_en', 'contact_name', 'contact_email', 'contact_phone', 'cr_number', 'vat_number', 'address_ar', 'notes'] as $optional) {
            $validated[$optional] = $validated[$optional] ?: null;
        }

        if ($this->editingId !== null) {
            $client = Client::query()->findOrFail($this->editingId);
            $this->authorize('update', $client);
            $client->update($validated);
        } else {
            $this->authorize('create', Client::class);
            Client::query()->create($validated);
        }

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.client_saved'));
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('delete', $client);

        $this->deletingId = $client->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $client = Client::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $client);

        if ($client->cohorts()->withTrashed()->exists()) {
            $this->deletingId = null;
            $this->dispatch('toast', type: 'error', message: __('courses.client_in_use'));

            return;
        }

        $client->delete();
        $this->deletingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.client_deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.clients-index', [
            'clients' => Client::query()
                ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name_ar', 'like', "%{$this->search}%")
                    ->orWhere('contact_name', 'like', "%{$this->search}%")))
                ->orderBy('name_ar')
                ->simplePaginate(15),
            'clientTypes' => ClientType::cases(),
            'deletingClient' => $this->deletingId !== null
                ? Client::query()->find($this->deletingId)
                : null,
        ])->title(__('courses.clients'));
    }
}
