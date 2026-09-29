<?php

namespace App\Actions\Community;

use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Services\ConfidenceCalculator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recomputes cached confidence for every public record. Needed when a source's
 * reliability or the current game version changes.
 */
class RecalculateConfidence
{
    public function handle(?int $sourceId = null): int
    {
        $calculator = new ConfidenceCalculator(GameVersion::current());
        $count = 0;

        foreach ([Marker::class, Item::class, Objective::class] as $model) {
            $model::query()
                ->when($sourceId !== null, fn (Builder $q) => $q->where('source_id', $sourceId))
                ->with('source')
                ->chunkById(500, function ($records) use ($calculator, &$count) {
                    foreach ($records as $record) {
                        $calculator->refresh($record);
                        $count++;
                    }
                });
        }

        return $count;
    }
}
