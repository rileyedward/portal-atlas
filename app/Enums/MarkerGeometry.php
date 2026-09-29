<?php

namespace App\Enums;

enum MarkerGeometry: string
{
    case Point = 'point';
    case Polygon = 'polygon';
    case Polyline = 'polyline';

    public function label(): string
    {
        return match ($this) {
            self::Point => 'Point',
            self::Polygon => 'Area',
            self::Polyline => 'Path',
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
