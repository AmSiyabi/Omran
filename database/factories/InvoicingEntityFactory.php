<?php

namespace Database\Factories;

use App\Models\InvoicingEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicingEntity>
 */
class InvoicingEntityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'جهة فوترة '.fake()->unique()->numberBetween(1, 9999),
            'type' => 'individual',
            'vat_registered' => false,
            'is_default' => false,
        ];
    }
}
