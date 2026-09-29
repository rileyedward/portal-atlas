<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Support\ConfidenceBreakdown;
use Carbon\CarbonInterface;

/**
 * Deterministic confidence model for public game data.
 *
 * Inputs: source reliability, community confirmations, admin verification,
 * open reports, and staleness (game version and verification age).
 */
class ConfidenceCalculator
{
    public const UNSOURCED_BASE = 20;

    public const PER_CONFIRMATION = 8;

    public const MAX_CONFIRMATION_BONUS = 30;

    public const ADMIN_VERIFIED_BONUS = 15;

    public const PER_OPEN_REPORT = 12;

    public const MAX_REPORT_PENALTY = 48;

    public const OUTDATED_VERSION_PENALTY = 15;

    public const NEVER_VERIFIED_PENALTY = 15;

    public const STALE_DAYS = 180;

    public const STALE_PENALTY = 10;

    public function __construct(private ?GameVersion $currentVersion = null) {}

    public function breakdown(Marker|Item|Objective $subject): ConfidenceBreakdown
    {
        $subject->loadMissing('source');

        $factors = [];
        $factors['source'] = $subject->source->reliability ?? self::UNSOURCED_BASE;

        $confirmations = $subject->verifications()->where('is_admin', false)->count();
        $factors['confirmations'] = min($confirmations * self::PER_CONFIRMATION, self::MAX_CONFIRMATION_BONUS);

        if ($subject->verifications()->where('is_admin', true)->exists()) {
            $factors['admin_verified'] = self::ADMIN_VERIFIED_BONUS;
        }

        $openReports = $subject->reports()->where('status', ReportStatus::Open->value)->count();
        if ($openReports > 0) {
            $factors['open_reports'] = -min($openReports * self::PER_OPEN_REPORT, self::MAX_REPORT_PENALTY);
        }

        $current = $this->currentVersion ??= GameVersion::current();
        if ($subject->verified_version_id === null) {
            $factors['never_verified'] = -self::NEVER_VERIFIED_PENALTY;
        } elseif ($current !== null && $subject->verified_version_id !== $current->id) {
            $factors['outdated_version'] = -self::OUTDATED_VERSION_PENALTY;
        }

        if ($subject->last_verified_at instanceof CarbonInterface && $subject->last_verified_at->lt(now()->subDays(self::STALE_DAYS))) {
            $factors['stale'] = -self::STALE_PENALTY;
        }

        $score = max(0, min(100, array_sum($factors)));

        if ($subject->confidence_override !== null) {
            return new ConfidenceBreakdown($subject->confidence_override, $factors, overridden: true);
        }

        return new ConfidenceBreakdown($score, $factors);
    }

    /**
     * Recalculate and persist the cached confidence columns.
     */
    public function refresh(Marker|Item|Objective $subject): ConfidenceBreakdown
    {
        $breakdown = $this->breakdown($subject);

        $subject->forceFill([
            'confidence' => max(0, min(100, array_sum($breakdown->factors))),
            'confirmations_count' => $subject->verifications()->count(),
            'open_reports_count' => $subject->reports()->where('status', ReportStatus::Open->value)->count(),
        ])->saveQuietly();

        return $breakdown;
    }
}
