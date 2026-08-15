<?php

namespace Database\Seeders;

use App\Models\InvoicingEntity;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The two equal partners (spec §1) + one default invoicing entity (§1.1).
 * Idempotent: re-running never overwrites existing passwords or data edits.
 * No credentials live in the repository (§10): passwords come from
 * SEED_*_PASSWORD env vars, or are generated and printed once.
 */
class OwnersSeeder extends Seeder
{
    public function run(): void
    {
        $owners = [
            [
                'env_key' => 'HAMAD',
                'name_ar' => 'حمد',
                'display_name_ar' => 'حمد',
                'email' => env('SEED_HAMAD_EMAIL', 'hamad@omran.local'),
            ],
            [
                'env_key' => 'AMMAR',
                'name_ar' => 'عمار',
                'display_name_ar' => 'عمار',
                'email' => env('SEED_AMMAR_EMAIL', 'ammar@omran.local'),
            ],
        ];

        foreach ($owners as $owner) {
            $user = User::query()->where('email', $owner['email'])->first();

            if ($user === null) {
                $password = env('SEED_'.$owner['env_key'].'_PASSWORD');

                if (! is_string($password) || $password === '') {
                    $password = Str::password(20);
                    $this->command?->warn(
                        "Generated password for {$owner['email']}: {$password} — store it now, it will not be shown again."
                    );
                }

                $user = User::create([
                    'name_ar' => $owner['name_ar'],
                    'email' => $owner['email'],
                    'password' => Hash::make($password),
                    'locale' => 'ar',
                    'is_active' => true,
                ]);

                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            }

            if (! $user->hasRole('owner')) {
                $user->assignRole('owner');
            }

            Partner::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name_ar' => $owner['display_name_ar'],
                    'bio_ar' => 'شريك مؤسس في مركز عمران للتدريب والاستشارات.',
                    'ownership_percent' => 50.00,
                    'effective_from' => now()->toDateString(),
                    'is_active' => true,
                    'public_profile_visible' => true,
                ],
            );
        }

        // §1.1: revenue must always record an invoicing vehicle. This default
        // is a placeholder until the partners register a real legal entity.
        InvoicingEntity::query()->firstOrCreate(
            ['is_default' => true],
            [
                'name_ar' => 'جهة الفوترة الافتراضية',
                'type' => 'individual',
                'vat_registered' => false,
                'notes' => 'وجهة مؤقتة — تُستبدل بالكيان القانوني الفعلي (سجل تجاري أو رقم مدني) قبل إصدار أي فاتورة رسمية. راجع القسم 1.1 من مواصفة البناء.',
            ],
        );
    }
}
