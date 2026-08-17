<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\CohortStatus;
use App\Enums\DocumentType;
use App\Finance\DocumentStorer;
use App\Finance\LedgerRecorder;
use App\Finance\Money;
use App\Models\Account;
use App\Models\Cohort;
use App\Support\Baisa;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Spec §12.3: the single most-used flow — a 40 OMR ad spend recorded from a
 * phone in fifteen seconds while walking. FAB → amount → category → save.
 */
class QuickAddExpense extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public string $amount = '';

    public string $category = '';

    public string $cohort_id = '';

    /** @var TemporaryUploadedFile|null */
    public $receipt = null;

    /** الفئات الشائعة: 5xxx تخص دفعة، 6xxx على مستوى المركز */
    public const CATEGORIES = ['5030', '5040', '5050', '5060', '5090', '6010', '6040', '6060', '6090'];

    public function show(): void
    {
        $this->authorize('finance.record_expense');

        $this->reset('amount', 'cohort_id', 'receipt');
        // افتراض ذكي: آخر فئة استُخدمت
        $this->category = session('quick_expense_category', '6090');
        $this->resetValidation();
        $this->open = true;
    }

    public function hide(): void
    {
        $this->open = false;
    }

    public function save(): void
    {
        $this->authorize('finance.record_expense');

        $validated = $this->validate([
            'amount' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'cohort_id' => [
                'nullable',
                Rule::exists('cohorts', 'id'),
                Rule::requiredIf(str_starts_with($this->category, '5')),
            ],
            'receipt' => ['nullable', 'file', 'mimes:jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'cohort_id.required' => __('courses.cohort').' — '.__('common.required_field'),
        ]);

        $amount = new Money(Baisa::fromString($validated['amount']));
        $recorder = app(LedgerRecorder::class);
        $userId = (int) auth()->id();
        $accountName = Account::byCode($validated['category'])->name_ar;
        $cohort = null;

        if (str_starts_with($validated['category'], '5')) {
            $cohort = Cohort::query()->findOrFail((int) $validated['cohort_id']);
            $entry = $recorder->recordDirectCost($cohort, $validated['category'], $amount, '1020', now(), $accountName, $userId);
        } else {
            $entry = $recorder->recordOperatingExpense($validated['category'], $amount, '1020', now(), $accountName, $userId);
        }

        if ($this->receipt !== null) {
            app(DocumentStorer::class)->attach($entry, $this->receipt, DocumentType::Receipt->value, $userId);
        }

        session(['quick_expense_category' => $validated['category']]);

        $this->open = false;
        $this->dispatch('toast', type: 'success', message: __('finance.quick_add_success', [
            'amount' => $amount->format(),
            'cohort' => $cohort !== null ? __('finance.quick_add_for_cohort', ['code' => $cohort->code]) : '',
        ]));
    }

    public function render(): View
    {
        return view('livewire.admin.finance.quick-add-expense', [
            'categories' => Account::query()
                ->whereIn('code', self::CATEGORIES)
                ->orderBy('code')
                ->get(['code', 'name_ar']),
            'cohorts' => $this->open
                ? Cohort::query()
                    ->whereIn('status', [CohortStatus::Open, CohortStatus::Closed, CohortStatus::Announced, CohortStatus::Delivered])
                    ->with('course:id,title_ar')
                    ->orderByDesc('starts_at')
                    ->get(['id', 'code', 'course_id', 'title_override_ar'])
                : collect(),
        ]);
    }
}
