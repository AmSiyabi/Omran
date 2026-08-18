<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Spec §8.9: every tax threshold is an editable setting, never a constant.
 * Values verified August 2026 — the owners must reconfirm with the OTA or
 * an advisor before relying on them (the CIT figures have a known
 * discrepancy between sources, spec §8.9 note).
 */
class TaxSettingsSeeder extends Seeder
{
    /** @var array<string, mixed> */
    private array $defaults = [
        // القيمة المضافة — بالبيسة
        'vat_mandatory_threshold_baisa' => 38_500_000,
        'vat_voluntary_threshold_baisa' => 19_250_000,
        'vat_rate_percent' => 5,
        // ضريبة الدخل على الشركات
        'cit_standard_rate_percent' => 15,
        'cit_reduced_rate_percent' => 3,
        'cit_reduced_capital_limit_baisa' => 60_000_000,
        'cit_reduced_income_limit_baisa' => 150_000_000,
        'cit_reduced_employee_limit' => 25,
        // ضريبة دخل الأفراد (من 2028)
        'pit_threshold_baisa' => 42_000_000,
        'pit_rate_percent' => 5,
        'pit_effective_date' => '2028-01-01',
    ];

    public function run(): void
    {
        foreach ($this->defaults as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::put($key, $value);
            }
        }
    }
}
