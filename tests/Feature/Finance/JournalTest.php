<?php

use App\Enums\JournalEntryStatus;
use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->user = User::factory()->create();
    $this->poster = app(JournalPoster::class);
});

function balancedLines(int $amount = 100000): array
{
    return [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money($amount)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money($amount)],
    ];
}

it('posts a balanced entry with sequential gapless numbering', function () {
    $first = $this->poster->post(now(), 'قيد أول', balancedLines(), $this->user->id);
    $second = $this->poster->post(now(), 'قيد ثانٍ', balancedLines(), $this->user->id);

    $year = now()->year;

    expect($first->entry_number)->toBe(sprintf('JE-%d-%06d', $year, 1))
        ->and($second->entry_number)->toBe(sprintf('JE-%d-%06d', $year, 2))
        ->and($first->lines)->toHaveCount(2)
        ->and($first->status)->toBe(JournalEntryStatus::Posted);
});

it('rejects an unbalanced entry', function () {
    $this->poster->post(now(), 'قيد أعرج', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(100)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(99)],
    ], $this->user->id);
})->throws(DomainException::class);

it('rejects a line with both debit and credit set', function () {
    $this->poster->post(now(), 'قيد مزدوج', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(100), 'credit' => new Money(100)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(0)],
    ], $this->user->id);
})->throws(DomainException::class);

it('rejects negative amounts', function () {
    $this->poster->post(now(), 'قيد سالب', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(-100)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(-100)],
    ], $this->user->id);
})->throws(DomainException::class);

it('throws when a posted entry is updated', function () {
    $entry = $this->poster->post(now(), 'قيد', balancedLines(), $this->user->id);

    $entry->description_ar = 'محاولة تعديل';
    $entry->save();
})->throws(DomainException::class);

it('throws when a posted entry is deleted', function () {
    $entry = $this->poster->post(now(), 'قيد', balancedLines(), $this->user->id);

    $entry->delete();
})->throws(DomainException::class);

it('throws when a journal line is updated or deleted', function () {
    $entry = $this->poster->post(now(), 'قيد', balancedLines(), $this->user->id);
    $line = $entry->lines()->first();

    expect(fn () => tap($line)->update(['memo_ar' => 'عبث']))->toThrow(DomainException::class)
        ->and(fn () => $line->delete())->toThrow(DomainException::class);
});

it('reverses an entry with swapped lines and flips the original', function () {
    $entry = $this->poster->post(now(), 'قيد أصلي', balancedLines(250000), $this->user->id);

    $reversal = $this->poster->reverse($entry, $this->user->id, 'خطأ إدخال');

    $originalFirst = $entry->lines()->get()[0];
    $reversedFirst = $reversal->lines()->get()[0];

    expect($entry->refresh()->status)->toBe(JournalEntryStatus::Reversed)
        ->and($entry->reversed_by_entry_id)->toBe($reversal->id)
        ->and($reversedFirst->debit_baisa->baisa)->toBe($originalFirst->credit_baisa->baisa)
        ->and($reversedFirst->credit_baisa->baisa)->toBe($originalFirst->debit_baisa->baisa);

    // القيد المعكوس نفسه لا يُعكس مرة أخرى
    expect(fn () => $this->poster->reverse($entry, $this->user->id))
        ->toThrow(DomainException::class);
});

it('balances every posted entry — sweep across all entries', function () {
    foreach (range(1, 5) as $i) {
        $this->poster->post(now(), "قيد {$i}", balancedLines($i * 1111), $this->user->id);
    }

    JournalEntry::query()->with('lines')->get()->each(function (JournalEntry $entry): void {
        $debits = $entry->lines->sum(fn ($line) => $line->debit_baisa->baisa);
        $credits = $entry->lines->sum(fn ($line) => $line->credit_baisa->baisa);

        expect($debits)->toBe($credits);
    });
});
