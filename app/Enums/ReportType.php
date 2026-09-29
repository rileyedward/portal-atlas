<?php

namespace App\Enums;

enum ReportType: string
{
    case IncorrectLocation = 'incorrect_location';
    case NoLongerExists = 'no_longer_exists';
    case WrongLoot = 'wrong_loot';
    case WrongExtraction = 'wrong_extraction';
    case WrongEnemy = 'wrong_enemy';
    case WrongObjective = 'wrong_objective';
    case Outdated = 'outdated';
    case Duplicate = 'duplicate';
    case MissingData = 'missing_data';
    case Bug = 'bug';
    case Suggestion = 'suggestion';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IncorrectLocation => 'Incorrect location',
            self::NoLongerExists => 'No longer exists',
            self::WrongLoot => 'Wrong loot',
            self::WrongExtraction => 'Wrong extraction',
            self::WrongEnemy => 'Wrong enemy',
            self::WrongObjective => 'Wrong objective',
            self::Outdated => 'Outdated information',
            self::Duplicate => 'Duplicate',
            self::MissingData => 'Missing information',
            self::Bug => "Something's broken",
            self::Suggestion => 'Suggestion or idea',
            self::Other => 'Other',
        };
    }

    /**
     * Whether the type fits feedback about a specific thing (a marker, item...),
     * general site feedback, or both. The form only offers relevant types.
     */
    public function scope(): string
    {
        return match ($this) {
            self::Bug, self::Suggestion => 'general',
            self::MissingData, self::Outdated, self::Other => 'both',
            default => 'subject',
        };
    }

    /**
     * @return list<array{value: string, label: string, scope: string}>
     */
    public static function feedbackOptions(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label(), 'scope' => $case->scope()], self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
