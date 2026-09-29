<?php

namespace Database\Factories;

use App\Models\MarkerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarkerCategory>
 */
class MarkerCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->word(),
            'color' => '#22c55e',
            'icon' => 'map-pin',
            'visible_by_default' => true,
        ];
    }
}
