<?php

namespace App\Actions\Community;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\Report;
use App\Models\User;
use App\Services\ConfidenceCalculator;
use App\Support\PublicCache;
use Illuminate\Validation\ValidationException;

/**
 * Stores player feedback. Feedback can be about a specific marker, item,
 * objective or map (which also lowers that record's confidence until it is
 * reviewed) or about the site in general.
 */
class SubmitReport
{
    public function __construct(private ConfidenceCalculator $confidence, private PublicCache $cache) {}

    /**
     * @param  array{email?: string|null, page_url?: string|null, context?: string|null, suggested_x?: float|null, suggested_y?: float|null}  $details
     */
    public function handle(Marker|Item|Objective|Map|null $subject, ReportType $type, ?string $message, ?User $user, string $ip, array $details = []): Report
    {
        $fingerprint = hash_hmac('sha256', $ip, (string) config('app.key'));
        $message = $message !== null ? trim(strip_tags($message)) : null;

        $mine = fn ($query) => $query
            ->where('status', ReportStatus::Open->value)
            ->where(fn ($q) => $user
                ? $q->where('user_id', $user->id)
                : $q->whereNull('user_id')->where('reporter_fingerprint', $fingerprint));

        $duplicate = $subject
            ? $mine($subject->reports())->exists()
            : $mine(Report::query()->whereNull('reportable_type'))->where('message', $message)->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'type' => 'You already sent this and it is still open. Thanks — a moderator will review it.',
            ]);
        }

        $report = new Report([
            'user_id' => $user?->id,
            'type' => $type,
            'message' => $message ?: null,
            'status' => ReportStatus::Open,
            'reporter_fingerprint' => $fingerprint,
            // Registered users are contacted through their account email.
            'email' => $user ? null : ($details['email'] ?? null),
            'page_url' => $details['page_url'] ?? null,
            'context' => isset($details['context']) ? strip_tags($details['context']) : null,
            'suggested_x' => $details['suggested_x'] ?? null,
            'suggested_y' => $details['suggested_y'] ?? null,
            'map_id' => match (true) {
                $subject instanceof Map => $subject->id,
                $subject instanceof Marker => $subject->map_id,
                $subject instanceof Objective => $subject->map_id,
                default => null,
            },
        ]);

        if ($subject) {
            $report->reportable()->associate($subject);
        }

        $report->save();

        if ($subject instanceof Marker || $subject instanceof Item || $subject instanceof Objective) {
            $this->confidence->refresh($subject);
            $this->cache->flush();
        }

        return $report;
    }
}
