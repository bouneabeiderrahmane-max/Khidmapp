<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'name' => ['fr' => ucfirst($word), 'ar' => $word],
        ];
    }
}
