<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\CohortStatus;
use App\Enums\DocumentType;
use App\Finance\DocumentStorer;
use App\Finance\JournalPoster;
use App\Finance\LedgerRecorder;
use App\Finance\Money;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Support\Baisa;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Lazy]
class FinanceHub extends Component
{
    use WithFileUploads, WithPagination;

    public function placeholder(): View
    {
        return view('livewire.admin.placeholders.finance');
    }

    /** الإجراء المفتوح حالياً: revenue|payment|direct_cost|opex|payout|contribution */
    public ?string $action = null;

    #[Locked]
    public ?int $reversingId = null;

    public string $amount = '';

    public string $date = '';

    public string $description = '';

    public string $cohort_id = '';

    public string $account_code = '';

    public string $cash_code = '1020';

    public string $partner_id = '';

    public string $reverse_reason = '';

    /** @var TemporaryUploadedFile|null */
    public $receipt = null;

    /** @var array<string, string> صلاحية كل إجراء */
    protected array $actionPermissions = [
        'revenue' => 'finance.record_income',
        'payment' => 'finance.record_income',
        'direct_cost' => 'finance.record_expense',
        'opex' => 'finance.record_expense',
        'payout' => 'partners.record_payout',
        'contribution' => 'partners.record_payout',
    ];

    public function mount(): void
    {
        $this->authorize('finance.view');
    }

    public function open(string $action): void
    {
        abort_unless(isset($this->actionPermissions[$action]), 404);
        $this->authorize($this->actionPermissions[$action]);

        $this->reset('amount', 'description', 'cohort_id', 'partner_id', 'receipt');
        $this->date = now()->timezone(config('app.display_timezone'))->format('Y-m-d');
        $this->cash_code = '1020';
        $this->account_code = match ($action) {
            'revenue' => '4010',
            'direct_cost' => '5030',
            'opex' => '6010',
            default => '',
        };
        $this->resetValidation();
        $this->action = $action;
    }

    public function close(): void
    {
        $this->action = null;
    }

    public function save(): void
    {
        abort_unless($this->action !== null && isset($this->actionPermissions[$this->action]), 404);
        $this->authorize($this->actionPermissions[$this->action]);

        $validated = $this->validate($this->rulesFor($this->action));

        $amount = new Money(Baisa::fromString($validated['amount']));
        $date = Carbon::parse($validated['date'], config('app.display_timezone'));
        $description = trim($validated['description']);
        $recorder = app(LedgerRecorder::class);
        $userId = (int) auth()->id();

        $entry = match ($this->action) {
            'revenue' => $recorder->recordRevenue(
                Cohort::query()->findOrFail((int) $validated['cohort_id']),
                $amount, $date, $description, $userId, $validated['account_code'],
            ),
            'payment' => $recorder->recordPayment(
                $validated['cohort_id'] !== '' ? Cohort::query()->findOrFail((int) $validated['cohort_id']) : null,
                $amount, $validated['cash_code'], $date, $description, $userId,
            ),
            'direct_cost' => $recorder->recordDirectCost(
                Cohort::query()->findOrFail((int) $validated['cohort_id']),
                $validated['account_code'], $amount, $validated['cash_code'], $date, $description, $userId,
            ),
            'opex' => $recorder->recordOperatingExpense(
                $validated['account_code'], $amount, $validated['cash_code'], $date, $description, $userId,
            ),
            'payout' => $recorder->recordPartnerPayout(
                Partner::query()->findOrFail((int) $validated['partner_id']),
                $amount, $validated['cash_code'], $date, $description, $userId,
            ),
            'contribution' => $recorder->recordCapitalContribution(
                Partner::query()->findOrFail((int) $validated['partner_id']),
                $amount, $validated['cash_code'], $date, $description, $userId,
            ),
            default => abort(404),
        };

        if ($this->receipt !== null) {
            app(DocumentStorer::class)->attach($entry, $this->receipt, DocumentType::Receipt->value, $userId);
        }

        $this->action = null;
        $this->dispatch('toast', type: 'success', message: __('finance.recorded_successfully', [
            'description' => $description,
            'amount' => $amount->format(),
        ]));
    }

    public function confirmReverse(int $entryId): void
    {
        $this->authorize('finance.reverse');

        $this->reverse_reason = '';
        $this->reversingId = JournalEntry::query()->where('status', 'posted')->findOrFail($entryId)->id;
    }

    public function cancelReverse(): void
    {
        $this->reversingId = null;
    }

    public function reverseEntry(): void
    {
        $this->authorize('finance.reverse');

        if ($this->reversingId === null) {
            return;
        }

        $this->validate(['reverse_reason' => ['required', 'string', 'max:500']]);

        $entry = JournalEntry::query()->findOrFail($this->reversingId);

        try {
            app(JournalPoster::class)->reverse($entry, (int) auth()->id(), $this->reverse_reason);
        } catch (DomainException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            $this->reversingId = null;

            return;
        }

        $this->reversingId = null;
        $this->dispatch('toast', type: 'success', message: __('finance.entry_reversed', ['number' => $entry->entry_number]));
    }

    public function render(): View
    {
        $entries = JournalEntry::query()
            ->with(['lines.account:id,code,name_ar'])
            ->orderByDesc('id')
            ->simplePaginate(10);

        return view('livewire.admin.finance.finance-hub', [
            'entries' => $entries,
            'cohorts' => Cohort::query()
                ->whereNotIn('status', [CohortStatus::Cancelled])
                ->with('course:id,title_ar')
                ->orderByDesc('starts_at')
                ->get(['id', 'code', 'course_id', 'title_override_ar']),
            'partners' => Partner::query()->where('is_active', true)->get(['id', 'display_name_ar']),
            'revenueAccounts' => Account::query()->where('code', 'like', '4%')->orderBy('code')->get(['code', 'name_ar']),
            'costAccounts' => Account::query()->where('code', 'like', '5%')->where('code', '!=', '5010')->orderBy('code')->get(['code', 'name_ar']),
            'expenseAccounts' => Account::query()->where('code', 'like', '6%')->orderBy('code')->get(['code', 'name_ar']),
            'cashAccounts' => Account::query()->whereIn('code', ['1010', '1020', '1030'])->orderBy('code')->get(['code', 'name_ar']),
            'reversingEntry' => $this->reversingId !== null ? JournalEntry::query()->find($this->reversingId) : null,
        ])->title(__('finance.finance'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesFor(string $action): array
    {
        $rules = [
            'amount' => ['required', 'regex:'.Baisa::INPUT_PATTERN],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'cash_code' => ['required', Rule::in(['1010', '1020', '1030'])],
            'receipt' => ['nullable', 'file', 'mimes:jpeg,png,webp,pdf', 'max:10240'],
        ];

        return $rules + match ($action) {
            'revenue' => [
                'cohort_id' => ['required', Rule::exists('cohorts', 'id')],
                'account_code' => ['required', Rule::in(['4010', '4020', '4030'])],
            ],
            'payment' => [
                'cohort_id' => ['nullable', Rule::exists('cohorts', 'id')],
            ],
            'direct_cost' => [
                'cohort_id' => ['required', Rule::exists('cohorts', 'id')],
                'account_code' => ['required', 'regex:/^50[2-9]0$/'],
            ],
            'opex' => [
                'account_code' => ['required', 'regex:/^60[1-9]0$/'],
            ],
            'payout', 'contribution' => [
                'partner_id' => ['required', Rule::exists('partners', 'id')],
            ],
            default => abort(404),
        };
    }
}
