<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'مقدمة في الذكاء الاصطناعي', 'أحكام الطهارة والصلاة', 'فقه المعاملات المالية',
            'مهارات العرض والإلقاء', 'تحليل البيانات للمبتدئين', 'التخطيط الاستراتيجي الشخصي',
            'أساسيات الأمن السيبراني', 'تدبر القرآن الكريم', 'إدارة المشاريع الاحترافية',
            'الكتابة الإبداعية بالعربية',
        ];

        $title = fake()->randomElement($titles).' '.fake()->numberBetween(1, 999);

        return [
            'slug' => Str::slug($title, '-', 'ar').'-'.fake()->unique()->numberBetween(1000, 999999),
            'category_id' => Category::factory(),
            'title_ar' => $title,
            'summary_ar' => 'برنامج تدريبي مكثف يمزج التأصيل النظري بالتطبيق العملي المباشر.',
            // static text — faker realText() with the Arabic locale builds a
            // huge Markov table and exhausts test memory
            'description_ar' => 'برنامج تدريبي متكامل يقدّم المفاهيم الأساسية للمجال عبر جلسات عملية مركزة، '
                .'ويأخذ المشارك خطوة بخطوة من التأصيل النظري إلى التطبيق المباشر على حالات واقعية، '
                .'مع تمارين جماعية ومشروع ختامي يرسّخ المهارات المكتسبة.',
            'outcomes_ar' => ['فهم الأسس النظرية', 'تطبيق المهارات عملياً', 'بناء مشروع ختامي'],
            'target_audience_ar' => 'المهتمون بالمجال من مختلف الخلفيات.',
            'duration_hours' => fake()->randomElement([6, 12, 18, 24]),
            'level' => fake()->randomElement(CourseLevel::cases()),
            'is_published' => fake()->boolean(70),
            'published_at' => now()->subDays(fake()->numberBetween(1, 120)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true, 'published_at' => now()]);
    }
}
