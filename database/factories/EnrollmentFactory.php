<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'full_name_ar' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('9#######'),
            'status' => EnrollmentStatus::Confirmed,
            'amount_due_baisa' => 45000,
            'payment_status' => PaymentStatus::Unpaid,
            'enrolled_at' => now(),
        ];
    }
}
