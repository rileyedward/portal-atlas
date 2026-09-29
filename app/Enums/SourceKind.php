<?php

namespace App\Enums;

enum SourceKind: string
{
    case Official = 'official';
    case OfficialMedia = 'official_media';
    case CommunityWiki = 'community_wiki';
    case CommunityTool = 'community_tool';
    case PlayerReport = 'player_report';
    case AdminObservation = 'admin_observation';
    case Datamine = 'datamine';

    public function label(): string
    {
        return match ($this) {
            self::Official => 'Official',
            self::OfficialMedia => 'Official media',
            self::CommunityWiki => 'Community wiki',
            self::CommunityTool => 'Community tool',
            self::PlayerReport => 'Player report',
            self::AdminObservation => 'Admin observation',
            self::Datamine => 'Datamine',
        };
    }

    /**
     * Default reliability (0-100) used when a source of this kind is created.
     */
    public function defaultReliability(): int
    {
        return match ($this) {
            self::Official => 95,
            self::OfficialMedia => 80,
            self::AdminObservation => 75,
            self::Datamine => 60,
            self::CommunityTool, self::CommunityWiki => 50,
            self::PlayerReport => 35,
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
