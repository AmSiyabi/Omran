<?php

namespace Database\Factories;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'د. '.fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('9#######'),
            'bio_ar' => 'مدرب معتمد بخبرة تتجاوز عشر سنوات في مجاله.',
            'specialization_ar' => fake()->randomElement(['الفقه وأصوله', 'الذكاء الاصطناعي', 'إدارة الأعمال', 'التقنية']),
            'is_public' => true,
        ];
    }
}
