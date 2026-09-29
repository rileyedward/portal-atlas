<?php

namespace App\Enums;

enum RecipeKind: string
{
    case Crafting = 'crafting';
    case Upgrade = 'upgrade';

    public function label(): string
    {
        return match ($this) {
            self::Crafting => 'Crafting',
            self::Upgrade => 'Upgrade',
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
