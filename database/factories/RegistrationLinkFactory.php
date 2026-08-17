<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\RegistrationLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationLink>
 */
class RegistrationLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'token' => RegistrationLink::generateToken(),
            'label_ar' => 'رابط عام',
            'requires_approval' => false,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
