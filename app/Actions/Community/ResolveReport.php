<?php

namespace App\Actions\Community;

use App\Enums\ReportStatus;
use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\Report;
use App\Models\User;
use App\Services\ConfidenceCalculator;

class ResolveReport
{
    public function __construct(private ConfidenceCalculator $confidence) {}

    public function handle(Report $report, ReportStatus $status, User $moderator, ?string $note = null): Report
    {
        $report->update([
            'status' => $status,
            'resolved_by' => $moderator->id,
            'resolution_note' => $note,
            'resolved_at' => $status === ReportStatus::Open ? null : now(),
        ]);

        $subject = $report->reportable;
        if ($subject instanceof Marker || $subject instanceof Item || $subject instanceof Objective) {
            $this->confidence->refresh($subject);
        }

        return $report;
    }
}
