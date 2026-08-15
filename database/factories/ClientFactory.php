<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => fake()->randomElement(['وزارة الأوقاف والشؤون الدينية', 'شركة النهضة', 'جمعية الرحمة', 'مؤسسة الإبداع']).' '.fake()->unique()->numberBetween(1, 9999),
            'type' => fake()->randomElement(ClientType::cases()),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('9#######'),
        ];
    }
}
