<?php

use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Livewire\Admin\Reports\ReportsHub;
use App\Livewire\Admin\Reports\TaxScreen;
use App\Livewire\Admin\SettingsPage;
use App\Models\Account;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    User::factory()->create()->partner()->create([
        'display_name_ar' => 'حمد', 'bio_ar' => 'شريك', 'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);

    $this->seed(ChartOfAccountsSeeder::class);
});

function reportsUser(string $role): User
{
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

it('gates the reports routes by permission per role', function () {
    // reports.view: مالك ومدير فقط
    $this->actingAs(reportsUser('owner'))->get(route('admin.reports'))->assertOk();
    $this->actingAs(reportsUser('admin'))->get(route('admin.reports'))->assertOk();
    $this->actingAs(reportsUser('coordinator'))->get(route('admin.reports'))->assertForbidden();
    $this->actingAs(reportsUser('viewer'))->get(route('admin.reports'))->assertForbidden();
});

it('keeps the tax screen and settings owner-only', function () {
    $this->actingAs(reportsUser('owner'))->get(route('admin.reports.tax'))->assertOk();
    $this->actingAs(reportsUser('admin'))->get(route('admin.reports.tax'))->assertForbidden();

    $this->actingAs(reportsUser('owner'))->get(route('admin.settings'))->assertOk();
    $this->actingAs(reportsUser('admin'))->get(route('admin.settings'))->assertForbidden();
});

it('re-authorizes the export action itself — view permission alone is not enough', function () {
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->givePermissionTo('reports.view'); // دون reports.export

    Livewire::actingAs($user)
        ->test(ReportsHub::class)
        ->call('exportXlsx')
        ->assertForbidden();
});

it('blocks tax and settings component mounts without the permission', function () {
    Livewire::actingAs(reportsUser('admin'))->test(TaxScreen::class)->assertForbidden();
    Livewire::actingAs(reportsUser('admin'))->test(SettingsPage::class)->assertForbidden();
});

it('downloads the XLSX and PDF exports for an owner', function () {
    app(JournalPoster::class)->post(now(), 'إيراد', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => new Money(640000)],
        ['account_id' => Account::byCode('4010')->id, 'credit' => new Money(640000)],
    ], User::query()->first()->id);

    $component = Livewire::actingAs(reportsUser('owner'))->test(ReportsHub::class);

    $component->call('exportXlsx')->assertFileDownloaded();
    $component->call('exportPdf')->assertFileDownloaded();
});

it('shows the non-removable tax disclaimer on the tax screen', function () {
    Livewire::actingAs(reportsUser('owner'))
        ->test(TaxScreen::class)
        ->assertSee(__('finance.tax_disclaimer'));
});

it('saves every tax threshold from the settings page', function () {
    Livewire::actingAs(reportsUser('owner'))
        ->test(SettingsPage::class)
        ->set('vat_mandatory', '40000.000')
        ->set('vat_voluntary', '20000.000')
        ->set('cit_reduced_rate', '5')
        ->set('cit_standard_rate', '12')
        ->set('cit_income_limit', '100000.000')
        ->set('pit_threshold', '50000.000')
        ->set('pit_date', '2029-01-01')
        ->set('opex_charged_to_center_pool', false)
        ->call('save')
        ->assertHasNoErrors();

    expect((int) Setting::get('vat_mandatory_threshold_baisa'))->toBe(40_000_000)
        ->and((int) Setting::get('vat_voluntary_threshold_baisa'))->toBe(20_000_000)
        ->and((int) Setting::get('cit_reduced_rate_percent'))->toBe(5)
        ->and((int) Setting::get('cit_standard_rate_percent'))->toBe(12)
        ->and((int) Setting::get('cit_reduced_income_limit_baisa'))->toBe(100_000_000)
        ->and((int) Setting::get('pit_threshold_baisa'))->toBe(50_000_000)
        ->and((string) Setting::get('pit_effective_date'))->toBe('2029-01-01')
        ->and((bool) Setting::get('opex_charged_to_center_pool'))->toBeFalse();
});

it('hides financial metrics on the dashboard from roles without finance.view', function () {
    $this->actingAs(reportsUser('coordinator'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(__('finance.dashboard_cash'));

    $this->actingAs(reportsUser('owner'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('finance.dashboard_cash'));
});
