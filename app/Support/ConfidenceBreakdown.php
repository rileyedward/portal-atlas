<?php

namespace App\Support;

/**
 * Detailed, internal explanation of a confidence score. Only the final score
 * and label are shown to players; admins see the full breakdown.
 */
final readonly class ConfidenceBreakdown
{
    /**
     * @param  array<string, int>  $factors  Signed contribution of each input.
     */
    public function __construct(
        public int $score,
        public array $factors,
        public bool $overridden = false,
    ) {}

    public function label(): string
    {
        return self::labelFor($this->score);
    }

    public static function labelFor(int $score): string
    {
        return match (true) {
            $score >= 80 => 'High',
            $score >= 55 => 'Medium',
            $score >= 30 => 'Low',
            default => 'Unverified',
        };
    }

    /**
     * @return array{score: int, label: string, factors: array<string, int>, overridden: bool}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'label' => $this->label(),
            'factors' => $this->factors,
            'overridden' => $this->overridden,
        ];
    }
}
