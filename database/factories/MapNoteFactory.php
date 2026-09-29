<?php

namespace Database\Factories;

use App\Models\Map;
use App\Models\MapNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapNote>
 */
class MapNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'map_id' => Map::factory(),
            'x' => fake()->randomFloat(4, 0, 100),
            'y' => fake()->randomFloat(4, 0, 100),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
        ];
    }
}
