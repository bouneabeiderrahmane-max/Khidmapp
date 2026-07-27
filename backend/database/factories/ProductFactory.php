<?php

namespace Database\Factories;

use App\Models\Boutique;
use App\Models\Product;
use App\Support\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'boutique_id' => Boutique::factory(),
            'external_ref' => fake()->unique()->uuid(),
            'name' => ['fr' => fake()->words(3, true), 'ar' => fake()->words(3, true)],
            'base_price_eur' => fake()->randomFloat(2, 5, 100),
            'status' => ProductStatus::ACTIVE,
            'last_synced_at' => now(),
        ];
    }
}
