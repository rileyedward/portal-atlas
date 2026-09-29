<?php

namespace App\Actions\Community;

use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\User;
use App\Services\ConfidenceCalculator;

/**
 * Records that a user has confirmed a data point for the current game version.
 * Admin/editor confirmations also mark the record as verified.
 */
class ConfirmAccuracy
{
    public function __construct(private ConfidenceCalculator $confidence) {}

    public function handle(Marker|Item|Objective $subject, User $user, ?string $note = null): void
    {
        $version = GameVersion::current();
        $isAdmin = $user->canManageContent();

        $subject->verifications()->updateOrCreate(
            ['user_id' => $user->id, 'game_version_id' => $version?->id],
            ['is_admin' => $isAdmin, 'note' => $note],
        );

        if ($isAdmin) {
            $subject->forceFill([
                'last_verified_at' => now(),
                'verified_version_id' => $version->id ?? $subject->verified_version_id,
            ])->saveQuietly();
        }

        $this->confidence->refresh($subject);
    }
}
