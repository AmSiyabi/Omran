<?php

namespace Database\Seeders;

use App\Models\DistributionPolicy;
use Illuminate\Database\Seeder;

/**
 * المادة الثانية من عقد الشراكة — الحالات الثلاث حرفياً (spec §8.5).
 * Do not "improve" the percentages (spec §0 rule 4).
 */
class DistributionPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'code' => 'EXTERNAL_INVITATION',
                'name_ar' => 'العمل الخارجي (دعوة شخصية لأحد الشريكين)',
                'description_ar' => '80% للشريك المنفذ و20% للمركز، بعد خصم التكاليف المباشرة.',
                'deliverer_share_percent' => 80.00,
                'external_fee_mode' => 'none',
            ],
            [
                'code' => 'OMRAN_ORGANIZED',
                'name_ar' => 'دورة من تنظيم عمران (تقديم أحد الشريكين)',
                'description_ar' => '70% للشريك المنفذ و30% للمركز، بعد خصم الإعلانات المأجورة — نص العقد صريح.',
                'deliverer_share_percent' => 70.00,
                'external_fee_mode' => 'none',
            ],
            [
                'code' => 'EXTERNAL_TRAINER',
                'name_ar' => 'استقطاب مدرب خارجي (تنظيم وتسويق عمران)',
                'description_ar' => 'أجر ثابت للمدرب يُتفق عليه مسبقاً حسب تقدير الربح، والباقي للمركز.',
                'deliverer_share_percent' => null,
                'external_fee_mode' => 'fixed',
            ],
        ];

        foreach ($policies as $policy) {
            DistributionPolicy::query()->updateOrCreate(
                ['code' => $policy['code']],
                $policy + [
                    'deduct_direct_costs_first' => true,
                    'center_split_mode' => 'by_ownership',
                    'is_active' => true,
                    'effective_from' => '2026-01-01',
                    'version' => 1,
                ],
            );
        }
    }
}
