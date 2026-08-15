<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The six categories from spec §7.2, with brand-consistent accent colors.
 * Idempotent.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'islamic', 'name_ar' => 'اسلامية', 'accent_color' => '#2E7D5B', 'sort_order' => 1],
            ['slug' => 'worship', 'name_ar' => 'عبادات', 'accent_color' => '#3E5C76', 'sort_order' => 2],
            ['slug' => 'ai', 'name_ar' => 'ذكاء اصطناعي', 'accent_color' => '#B8893A', 'sort_order' => 3],
            ['slug' => 'tech', 'name_ar' => 'تقنية', 'accent_color' => '#16202F', 'sort_order' => 4],
            ['slug' => 'self-development', 'name_ar' => 'تطوير ذات', 'accent_color' => '#A8433E', 'sort_order' => 5],
            ['slug' => 'professional', 'name_ar' => 'مهارات مهنية', 'accent_color' => '#6A7383', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true],
            );
        }
    }
}
