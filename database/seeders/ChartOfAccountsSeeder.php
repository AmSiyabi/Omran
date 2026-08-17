<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Partner;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The fixed chart from spec §8.2. Idempotent. Partner equity accounts
 * (301x capital / 302x current) are generated per active partner in id
 * order — first partner takes x=0, second x=1.
 */
class ChartOfAccountsSeeder extends Seeder
{
    /** @var array<int, array{string, string, string, string}> */
    private array $accounts = [
        // الأصول
        ['1010', 'الصندوق (نقد)', 'Cash on hand', 'asset'],
        ['1020', 'حساب المركز البنكي', 'Omran bank account', 'asset'],
        ['1030', 'محافظ إلكترونية', 'Digital wallets', 'asset'],
        ['1100', 'ذمم مدينة (مستحقات على العملاء)', 'Accounts receivable', 'asset'],
        ['1200', 'مصروفات مدفوعة مقدماً', 'Prepaid expenses', 'asset'],
        // الالتزامات
        ['2010', 'ذمم دائنة', 'Accounts payable', 'liability'],
        ['2020', 'مستحقات مدربين خارجيين', 'External trainer payable', 'liability'],
        ['2100', 'ضريبة القيمة المضافة المستحقة', 'VAT payable', 'liability'],
        ['2200', 'إيرادات مقبوضة مقدماً', 'Deferred revenue', 'liability'],
        // حقوق الملكية غير المرتبطة بشريك
        ['3090', 'الأرباح المحتجزة', 'Retained earnings', 'equity'],
        // الإيرادات
        ['4010', 'إيرادات الدورات والورش', 'Course and workshop revenue', 'revenue'],
        ['4020', 'إيرادات الاستشارات', 'Consulting revenue', 'revenue'],
        ['4030', 'إيرادات أخرى', 'Other revenue', 'revenue'],
        // التكاليف المباشرة
        ['5010', 'حصة الشريك المنفذ', 'Delivering-partner share', 'direct_cost'],
        ['5020', 'أتعاب مدرب خارجي', 'External trainer fee', 'direct_cost'],
        ['5030', 'إعلانات مأجورة ودعاية مباشرة', 'Paid advertising and direct promotion', 'direct_cost'],
        ['5040', 'قاعات وضيافة', 'Venue and hospitality', 'direct_cost'],
        ['5050', 'مواد تدريبية وطباعة', 'Materials and printing', 'direct_cost'],
        ['5060', 'سفر وإقامة', 'Travel and accommodation', 'direct_cost'],
        ['5090', 'تكاليف مباشرة أخرى', 'Other direct costs', 'direct_cost'],
        // المصروفات التشغيلية
        ['6010', 'اشتراكات برمجية ومنصات', 'Software and platform subscriptions', 'operating_expense'],
        ['6020', 'استضافة ونطاقات', 'Hosting and domains', 'operating_expense'],
        ['6030', 'رسوم بنكية وبوابات دفع', 'Bank and payment fees', 'operating_expense'],
        ['6040', 'تصميم ومحتوى', 'Design and content', 'operating_expense'],
        ['6050', 'رسوم حكومية وتراخيص', 'Government fees and licences', 'operating_expense'],
        ['6060', 'تسويق عام (غير مخصص لدورة)', 'General marketing', 'operating_expense'],
        ['6090', 'مصروفات إدارية أخرى', 'Other administrative expenses', 'operating_expense'],
    ];

    public function run(): void
    {
        foreach ($this->accounts as [$code, $nameAr, $nameEn, $type]) {
            Account::query()->updateOrCreate(
                ['code' => $code],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'type' => $type, 'is_system' => true],
            );
        }

        // حسابات الشريكين: رأس المال 301x والجاري 302x
        $partners = Partner::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->take(2)
            ->get();

        foreach ($partners->values() as $index => $partner) {
            Account::query()->updateOrCreate(
                ['code' => '301'.$index],
                [
                    'name_ar' => 'رأس المال — '.$partner->display_name_ar,
                    'name_en' => 'Capital — '.($partner->display_name_en ?? $partner->display_name_ar),
                    'type' => 'equity',
                    'partner_id' => $partner->id,
                    'is_system' => true,
                ],
            );

            Account::query()->updateOrCreate(
                ['code' => '302'.$index],
                [
                    'name_ar' => 'الحساب الجاري — '.$partner->display_name_ar,
                    'name_en' => 'Partner current account — '.($partner->display_name_en ?? $partner->display_name_ar),
                    'type' => 'equity',
                    'partner_id' => $partner->id,
                    'is_system' => true,
                ],
            );
        }

        // §8.6: المصروفات التشغيلية تُخصم من وعاء المركز قبل القسمة (افتراضياً)
        if (Setting::get('opex_charged_to_center_pool') === null) {
            Setting::put('opex_charged_to_center_pool', true);
        }
    }
}
