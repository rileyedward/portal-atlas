<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Marker;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reportable_type' => (new Marker)->getMorphClass(),
            'reportable_id' => Marker::factory(),
            'type' => ReportType::IncorrectLocation,
            'message' => fake()->sentence(),
            'status' => ReportStatus::Open,
        ];
    }
}
