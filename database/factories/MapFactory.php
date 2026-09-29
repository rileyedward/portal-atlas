<?php

namespace Database\Factories;

use App\Enums\MapStatus;
use App\Models\Map;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Map>
 */
class MapFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'status' => MapStatus::Published,
            'width' => 2000,
            'height' => 2000,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => MapStatus::Draft]);
    }
}
