<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marker>
 */
class MarkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'map_id' => Map::factory(),
            'marker_type_id' => MarkerType::factory(),
            'name' => fake()->word().' '.fake()->word(),
            'x' => fake()->randomFloat(4, 0, 100),
            'y' => fake()->randomFloat(4, 0, 100),
            'status' => ContentStatus::Published,
            'is_visible' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ContentStatus::Draft]);
    }
}
