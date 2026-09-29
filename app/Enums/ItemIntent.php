<?php

namespace App\Enums;

enum ItemIntent: string
{
    case Need = 'need';
    case Have = 'have';
    case DontNeed = 'dont_need';
    case Keep = 'keep';
    case Sell = 'sell';
    case Quest = 'quest';
    case Upgrade = 'upgrade';

    public function label(): string
    {
        return match ($this) {
            self::Need => 'Need',
            self::Have => 'Have',
            self::DontNeed => "Don't need",
            self::Keep => 'Keep',
            self::Sell => 'Sell',
            self::Quest => 'Quest item',
            self::Upgrade => 'Upgrade item',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
