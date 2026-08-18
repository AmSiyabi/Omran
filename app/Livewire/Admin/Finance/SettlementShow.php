<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Finance\SettlementService;
use App\Models\Settlement;
use DomainException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One screen, full breakdown, single confirm (spec §12.3). Posted
 * settlements render exclusively from the frozen snapshot.
 */
#[Layout('components.layouts.admin')]
class SettlementShow extends Component
{
    #[Locked]
    public int $settlementId;

    public bool $confirming = false;

    public bool $accept_loss = false;

    public string $override_reason = '';

    public bool $reversing = false;

    public string $reverse_reason = '';

    public function mount(int $settlement): void
    {
        $this->authorize('finance.settle');
        $this->settlementId = Settlement::query()->findOrFail($settlement)->id;
    }

    public function recompute(): void
    {
        $this->authorize('finance.settle');

        try {
            app(SettlementService::class)->recompute($this->settlement());
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('finance.settlement_recomputed'));
    }

    public function openConfirm(): void
    {
        $this->authorize('finance.settle');

        $this->accept_loss = false;
        $this->override_reason = '';
        $this->resetValidation();
        $this->confirming = true;
    }

    public function closeConfirm(): void
    {
        $this->confirming = false;
    }

    public function confirmSettlement(): void
    {
        // إعادة التخويل في كل استدعاء — mount() ليس بوابة (spec §9.4)
        $this->authorize('finance.settle');

        $settlement = $this->settlement();
        $flags = $settlement->snapshot['flags'] ?? [];

        try {
            $service = app(SettlementService::class);
            $acceptLoss = $this->accept_loss || in_array('OVERCOMMITTED', $flags, true);
            $override = $this->override_reason !== '' ? $this->override_reason : null;

            $settlement = $settlement->type === SettlementType::Monthly
                ? $service->confirmMonthly($settlement, (int) auth()->id(), $acceptLoss, $override)
                : $service->confirm($settlement, (int) auth()->id(), $acceptLoss, $override);
        } catch (DomainException $exception) {
            $message = match ($exception->getMessage()) {
                'LOSS' => __('finance.flag_loss_text'),
                'OVERCOMMITTED' => __('finance.flag_overcommitted_text'),
                default => $exception->getMessage(),
            };

            $this->dispatch('toast', type: 'error', message: $message);

            return;
        }

        $this->confirming = false;
        $this->dispatch('toast', type: 'success', message: __('finance.settlement_confirmed', ['number' => $settlement->settlement_number]));
    }

    public function openReverse(): void
    {
        $this->authorize('finance.reverse');

        $this->reverse_reason = '';
        $this->resetValidation();
        $this->reversing = true;
    }

    public function closeReverse(): void
    {
        $this->reversing = false;
    }

    public function reverseSettlement(): void
    {
        $this->authorize('finance.reverse');

        $this->validate(['reverse_reason' => ['required', 'string', 'max:500']]);

        try {
            app(SettlementService::class)->reverse($this->settlement(), (int) auth()->id(), $this->reverse_reason);
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->reversing = false;

            return;
        }

        $this->reversing = false;
        $this->dispatch('toast', type: 'success', message: __('finance.settlement_reversed'));
    }

    public function render(): View
    {
        $settlement = Settlement::query()
            ->with(['cohort.course:id,title_ar', 'journalEntry'])
            ->findOrFail($this->settlementId);

        $snapshot = $settlement->snapshot ?? [];

        return view('livewire.admin.finance.settlement-show', [
            'settlement' => $settlement,
            'snapshot' => $snapshot,
            'flags' => $snapshot['flags'] ?? [],
            'isDraft' => $settlement->status === SettlementStatus::Draft,
        ])->title($settlement->settlement_number);
    }

    protected function settlement(): Settlement
    {
        return Settlement::query()->findOrFail($this->settlementId);
    }
}
