<?php

namespace Database\Factories;

use App\Models\MarkerCategory;
use App\Models\MarkerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarkerType>
 */
class MarkerTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'marker_category_id' => MarkerCategory::factory(),
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->word(),
            'icon' => 'map-pin',
            'geometry' => 'point',
        ];
    }
}
