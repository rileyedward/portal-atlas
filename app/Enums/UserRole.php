<?php

namespace App\Enums;

enum UserRole: string
{
    case Player = 'player';
    case Editor = 'editor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Player',
            self::Editor => 'Editor',
            self::Admin => 'Admin',
        };
    }

    public function canManageContent(): bool
    {
        return $this === self::Editor || $this === self::Admin;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
