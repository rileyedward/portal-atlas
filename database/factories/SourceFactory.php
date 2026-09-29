<?php

namespace Database\Factories;

use App\Enums\SourceKind;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'kind' => SourceKind::CommunityWiki,
            'url' => fake()->url(),
            'reliability' => 50,
        ];
    }
}
