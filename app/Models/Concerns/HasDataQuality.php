<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Models\GameVersion;
use App\Models\Report;
use App\Models\Source;
use App\Models\Verification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared provenance, versioning and confidence behaviour for public game data.
 *
 * @property int|null $source_id
 * @property string|null $source_url
 * @property int $confidence
 * @property int|null $confidence_override
 * @property int $confirmations_count
 * @property int $open_reports_count
 * @property CarbonInterface|null $last_verified_at
 * @property int|null $introduced_version_id
 * @property int|null $verified_version_id
 * @property ContentStatus $status
 */
trait HasDataQuality
{
    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return BelongsTo<GameVersion, $this>
     */
    public function introducedVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'introduced_version_id');
    }

    /**
     * @return BelongsTo<GameVersion, $this>
     */
    public function verifiedVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'verified_version_id');
    }

    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * @return MorphMany<Verification, $this>
     */
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), ContentStatus::Published->value);
    }

    public function effectiveConfidence(): int
    {
        return $this->confidence_override ?? $this->confidence;
    }
}
