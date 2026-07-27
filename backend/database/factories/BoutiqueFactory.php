<?php

namespace Database\Factories;

use App\Models\Boutique;
use App\Support\BoutiqueStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Boutique>
 */
class BoutiqueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'base_url' => fake()->url(),
            'country_code' => 'ES',
            'currency_code' => 'EUR',
            'status' => BoutiqueStatus::EN_TEST,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => BoutiqueStatus::ACTIVE]);
    }
}
