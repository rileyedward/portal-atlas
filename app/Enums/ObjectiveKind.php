<?php

namespace App\Enums;

enum ObjectiveKind: string
{
    case Objective = 'objective';
    case Quest = 'quest';
    case Investigation = 'investigation';
    case Contract = 'contract';
    case Mission = 'mission';

    public function label(): string
    {
        return match ($this) {
            self::Objective => 'Objective',
            self::Quest => 'Quest',
            self::Investigation => 'Investigation',
            self::Contract => 'Contract',
            self::Mission => 'Mission target',
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
