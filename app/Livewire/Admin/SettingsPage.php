<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Support\Baisa;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Spec Phase 6 acceptance: every tax threshold editable, nothing hardcoded.
 * Owner-only (settings.manage).
 */
#[Layout('components.layouts.admin')]
class SettingsPage extends Component
{
    public bool $opex_charged_to_center_pool = true;

    public string $vat_mandatory = '';

    public string $vat_voluntary = '';

    public string $cit_reduced_rate = '';

    public string $cit_standard_rate = '';

    public string $cit_income_limit = '';

    public string $pit_threshold = '';

    public string $pit_date = '';

    public function mount(): void
    {
        $this->authorize('settings.manage');

        $this->opex_charged_to_center_pool = (bool) Setting::get('opex_charged_to_center_pool', true);
        $this->vat_mandatory = Baisa::toString((int) Setting::get('vat_mandatory_threshold_baisa', 38_500_000));
        $this->vat_voluntary = Baisa::toString((int) Setting::get('vat_voluntary_threshold_baisa', 19_250_000));
        $this->cit_reduced_rate = (string) Setting::get('cit_reduced_rate_percent', 3);
        $this->cit_standard_rate = (string) Setting::get('cit_standard_rate_percent', 15);
        $this->cit_income_limit = Baisa::toString((int) Setting::get('cit_reduced_income_limit_baisa', 150_000_000));
        $this->pit_threshold = Baisa::toString((int) Setting::get('pit_threshold_baisa', 42_000_000));
        $this->pit_date = (string) Setting::get('pit_effective_date', '2028-01-01');
    }

    public function save(): void
    {
        $this->authorize('settings.manage');

        $validated = $this->validate([
            'opex_charged_to_center_pool' => ['boolean'],
            'vat_mandatory' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'vat_voluntary' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'cit_reduced_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'cit_standard_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'cit_income_limit' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'pit_threshold' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'pit_date' => ['required', 'date'],
        ]);

        Setting::put('opex_charged_to_center_pool', (bool) $validated['opex_charged_to_center_pool']);
        Setting::put('vat_mandatory_threshold_baisa', Baisa::fromString($validated['vat_mandatory']));
        Setting::put('vat_voluntary_threshold_baisa', Baisa::fromString($validated['vat_voluntary']));
        Setting::put('cit_reduced_rate_percent', (int) $validated['cit_reduced_rate']);
        Setting::put('cit_standard_rate_percent', (int) $validated['cit_standard_rate']);
        Setting::put('cit_reduced_income_limit_baisa', Baisa::fromString($validated['cit_income_limit']));
        Setting::put('pit_threshold_baisa', Baisa::fromString($validated['pit_threshold']));
        Setting::put('pit_effective_date', $validated['pit_date']);

        $this->dispatch('toast', type: 'success', message: __('finance.settings_saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings-page')->title(__('finance.settings'));
    }
}
