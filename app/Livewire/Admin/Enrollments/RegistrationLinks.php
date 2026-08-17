<?php

namespace App\Livewire\Admin\Enrollments;

use App\Models\Cohort;
use App\Models\RegistrationLink;
use App\Support\Baisa;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Nested on the cohort page — generate/revoke join links (spec §7.3).
 */
class RegistrationLinks extends Component
{
    #[Locked]
    public int $cohortId;

    #[Locked]
    public ?int $revokingId = null;

    public bool $showForm = false;

    public string $label_ar = '';

    public string $price_override = '';

    public string $max_uses = '';

    public string $expires_at = '';

    public bool $requires_approval = false;

    public function mount(int $cohortId): void
    {
        $this->cohortId = $cohortId;
        $this->authorize('viewAny', RegistrationLink::class);
    }

    public function create(): void
    {
        $this->authorize('create', RegistrationLink::class);

        $this->reset('label_ar', 'price_override', 'max_uses', 'expires_at');
        $this->requires_approval = false;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', RegistrationLink::class);

        $validated = $this->validate([
            'label_ar' => ['nullable', 'string', 'max:255'],
            'price_override' => ['nullable', 'regex:'.Baisa::INPUT_PATTERN],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $cohort = Cohort::query()->findOrFail($this->cohortId);

        RegistrationLink::query()->create([
            'cohort_id' => $cohort->id,
            'token' => RegistrationLink::generateToken(),
            'label_ar' => $validated['label_ar'] ?: null,
            'price_override_baisa' => $validated['price_override'] !== null && $validated['price_override'] !== ''
                ? Baisa::fromString($validated['price_override'])
                : null,
            'max_uses' => $validated['max_uses'] !== null && $validated['max_uses'] !== '' ? (int) $validated['max_uses'] : null,
            'expires_at' => $validated['expires_at'] ?: null,
            'requires_approval' => $this->requires_approval,
            'created_by' => auth()->id(),
        ]);

        $this->showForm = false;
        $this->dispatch('toast', type: 'success', message: __('courses.link_created'));
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function confirmRevoke(int $id): void
    {
        $link = $this->findLink($id);
        $this->authorize('revoke', $link);

        $this->revokingId = $link->id;
    }

    public function cancelRevoke(): void
    {
        $this->revokingId = null;
    }

    public function revoke(): void
    {
        if ($this->revokingId === null) {
            return;
        }

        $link = $this->findLink($this->revokingId);
        $this->authorize('revoke', $link);

        $link->update(['is_active' => false]);

        $this->revokingId = null;
        $this->dispatch('toast', type: 'success', message: __('courses.link_revoked'));
    }

    public function render(): View
    {
        return view('livewire.admin.enrollments.registration-links', [
            'links' => RegistrationLink::query()
                ->where('cohort_id', $this->cohortId)
                ->withCount('enrollments')
                ->latest('id')
                ->get(),
            'revokingLink' => $this->revokingId !== null ? $this->findLink($this->revokingId) : null,
        ]);
    }

    protected function findLink(int $id): RegistrationLink
    {
        return RegistrationLink::query()
            ->where('cohort_id', $this->cohortId)
            ->findOrFail($id);
    }
}
