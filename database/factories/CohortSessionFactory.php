<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\CohortSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CohortSession>
 */
class CohortSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'cohort_id' => Cohort::factory(),
            'session_number' => fake()->unique()->numberBetween(1, 10000),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 hours'),
        ];
    }
}
