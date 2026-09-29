<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Str;
use Throwable;

/**
 * Anonymous aggregate analytics. Deliberately stores no user, IP or device data.
 */
class Analytics
{
    public const EVENTS = ['map_view', 'search', 'marker_open', 'filter_toggle', 'item_view'];

    public function record(string $name, ?string $subjectType = null, ?int $subjectId = null, ?string $term = null, ?int $resultCount = null): void
    {
        if (! in_array($name, self::EVENTS, true) || ! config('services.analytics.enabled', true)) {
            return;
        }

        try {
            AnalyticsEvent::create([
                'name' => $name,
                'subject_type' => $subjectType ? Str::limit($subjectType, 40, '') : null,
                'subject_id' => $subjectId,
                'term' => $term !== null ? Str::limit(Str::lower(trim($term)), 120, '') : null,
                'result_count' => $resultCount,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
