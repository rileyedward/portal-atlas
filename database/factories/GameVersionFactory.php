<?php

namespace Database\Factories;

use App\Models\GameVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameVersion>
 */
class GameVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('0.#.##'),
            'released_at' => fake()->date(),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true]);
    }
}
