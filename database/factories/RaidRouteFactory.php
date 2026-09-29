<?php

namespace Database\Factories;

use App\Models\Map;
use App\Models\RaidRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaidRoute>
 */
class RaidRouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'map_id' => Map::factory(),
            'name' => fake()->sentence(3),
            'points' => [
                ['x' => 10.0, 'y' => 10.0, 'label' => 'Start'],
                ['x' => 50.0, 'y' => 50.0, 'label' => 'End'],
            ],
        ];
    }
}
