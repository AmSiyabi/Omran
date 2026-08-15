<?php

namespace Database\Factories;

use App\Enums\CohortStatus;
use App\Enums\DeliveryMode;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\InvoicingEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
class CohortFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+4 months');

        return [
            'course_id' => Course::factory(),
            'code' => 'CO-'.fake()->unique()->numerify('######'),
            'delivery_mode' => fake()->randomElement(DeliveryMode::cases()),
            'venue_ar' => 'قاعة التدريب الرئيسية',
            'city_ar' => fake()->randomElement(['مسقط', 'صلالة', 'نزوى', 'صحار']),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 days'),
            'capacity' => fake()->numberBetween(10, 40),
            'price_baisa' => fake()->numberBetween(15, 120) * 1000,
            'invoicing_entity_id' => InvoicingEntity::query()->value('id') ?? InvoicingEntity::factory(),
            'status' => CohortStatus::Draft,
        ];
    }
}
