<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $reportable_type
 * @property int|null $reportable_id
 * @property int|null $map_id
 * @property string|null $context
 * @property string|null $page_url
 * @property string|null $email
 * @property float|null $suggested_x
 * @property float|null $suggested_y
 * @property int|null $user_id
 * @property ReportType $type
 * @property string|null $message
 * @property ReportStatus $status
 * @property string|null $reporter_fingerprint
 * @property int|null $resolved_by
 * @property string|null $resolution_note
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property-read Marker|Item|Objective|Map|null $reportable
 * @property-read Map|null $map
 * @property-read User|null $user
 */
#[Fillable([
    'reportable_type', 'reportable_id', 'map_id', 'user_id', 'type', 'message', 'context', 'page_url', 'email',
    'suggested_x', 'suggested_y', 'status', 'reporter_fingerprint',
    'resolved_by', 'resolution_note', 'resolved_at',
])]
#[Hidden(['reporter_fingerprint', 'email'])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
            'suggested_x' => 'float',
            'suggested_y' => 'float',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<Map, $this>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function hasSuggestedPosition(): bool
    {
        return $this->suggested_x !== null && $this->suggested_y !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
