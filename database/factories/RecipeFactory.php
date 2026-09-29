<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\RecipeKind;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word().' '.fake()->word();

        return [
            'kind' => RecipeKind::Crafting,
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'status' => ContentStatus::Published,
        ];
    }
}
