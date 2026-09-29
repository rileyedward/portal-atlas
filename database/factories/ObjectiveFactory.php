<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\ObjectiveKind;
use App\Models\Objective;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Objective>
 */
class ObjectiveFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word().' '.fake()->word();

        return [
            'kind' => ObjectiveKind::Objective,
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'status' => ContentStatus::Published,
        ];
    }
}
