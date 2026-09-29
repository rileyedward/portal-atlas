<?php

namespace Database\Seeders;

use App\Models\MarkerCategory;
use App\Models\MarkerType;
use Illuminate\Database\Seeder;

/**
 * Seeds the marker category/type taxonomy. This is application configuration,
 * not game data: it describes *kinds* of things that can be placed on a map.
 * Admins can extend it from the admin panel.
 */
class MarkerTaxonomySeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, color: string, icon: string, visible: bool, types: array<string, array{0: string, 1: string, 2?: string}>}>
     */
    public const TAXONOMY = [
        'navigation' => [
            'name' => 'Locations', 'color' => '#94a3b8', 'icon' => 'map-pin', 'visible' => true,
            'types' => [
                'named-location' => ['Named location', 'flag'],
                'landmark' => ['Landmark', 'landmark'],
                'building' => ['Building', 'building-2'],
                'road-path' => ['Road / path', 'route', 'polyline'],
                'area' => ['Area', 'square-dashed', 'polygon'],
                'spawn' => ['Spawn point', 'locate-fixed'],
                'vehicle-spawn' => ['Vehicle spawn', 'car'],
                'portal' => ['Portal', 'orbit'],
                'locked-door' => ['Locked door', 'lock'],
            ],
        ],
        'extraction' => [
            'name' => 'Extracts', 'color' => '#22c55e', 'icon' => 'door-open', 'visible' => true,
            'types' => [
                'extraction-point' => ['Extraction point', 'door-open'],
                'conditional-extraction' => ['Conditional extraction', 'key-round'],
                'dynamic-extraction' => ['Dynamic extraction', 'timer'],
                'final-extraction' => ['Final extraction', 'log-out'],
            ],
        ],
        'loot' => [
            'name' => 'Loot', 'color' => '#f59e0b', 'icon' => 'package', 'visible' => true,
            'types' => [
                'loot-location' => ['Loot location', 'package'],
                'high-value-loot' => ['High-value loot', 'gem'],
                'resource' => ['Resource', 'pickaxe'],
                'container' => ['Container', 'archive'],
                'special-item' => ['Special item', 'sparkles'],
                'container-tier-2' => ['Container (tier 2)', 'box'],
                'container-tier-3' => ['Container (tier 3)', 'boxes'],
                'safe' => ['Safe', 'vault'],
                'medical-supplies' => ['Medical supplies', 'heart-pulse'],
                'ammo-box' => ['Ammo box', 'package-open'],
                'artifact' => ['Artifact', 'diamond'],
                'documents' => ['Documents', 'file-text'],
                'key-spawn' => ['Key spawn', 'key'],
                'interactive' => ['Interactive', 'hand'],
            ],
        ],
        'objectives' => [
            'name' => 'Objectives', 'color' => '#38bdf8', 'icon' => 'crosshair', 'visible' => true,
            'types' => [
                'objective' => ['Objective', 'crosshair'],
                'quest' => ['Quest', 'scroll-text'],
                'investigation' => ['Investigation', 'search'],
                'contract' => ['Contract', 'file-signature'],
                'mission-target' => ['Mission target', 'target'],
                'puzzle' => ['Puzzle', 'puzzle'],
            ],
        ],
        'threats' => [
            'name' => 'Threats', 'color' => '#ef4444', 'icon' => 'skull', 'visible' => false,
            'types' => [
                'enemy' => ['Enemy', 'skull'],
                'enemy-camp' => ['Enemy camp', 'tent'],
                'monster' => ['Monster', 'bug'],
                'boss' => ['Boss', 'crown'],
                'spawn-zone' => ['Monster spawn zone', 'target'],
                'anomaly' => ['Anomaly', 'atom'],
                'hazard' => ['Hazard', 'triangle-alert'],
                'pvp-hotspot' => ['PvP hotspot', 'swords'],
            ],
        ],
    ];

    public function run(): void
    {
        $categoryOrder = 0;

        foreach (self::TAXONOMY as $slug => $definition) {
            $category = MarkerCategory::updateOrCreate(['slug' => $slug], [
                'name' => $definition['name'],
                'color' => $definition['color'],
                'icon' => $definition['icon'],
                'visible_by_default' => $definition['visible'],
                'sort_order' => $categoryOrder++,
            ]);

            $typeOrder = 0;
            foreach ($definition['types'] as $typeSlug => $type) {
                MarkerType::updateOrCreate(['slug' => $typeSlug], [
                    'marker_category_id' => $category->id,
                    'name' => $type[0],
                    'icon' => $type[1],
                    'geometry' => $type[2] ?? 'point',
                    'sort_order' => $typeOrder++,
                ]);
            }
        }
    }
}
