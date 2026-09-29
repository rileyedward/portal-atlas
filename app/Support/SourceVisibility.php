<?php

namespace App\Support;

use App\Models\User;

/**
 * Where data came from (source names, links, notes, image attribution and raw
 * import metadata) is shown to editors and admins only. Players still see
 * confidence, verification dates and can confirm or report entries.
 */
final class SourceVisibility
{
    /** Marker metadata keys that are safe to show players. */
    public const PUBLIC_MARKER_METADATA = ['conditions'];

    /** Item metadata keys that are safe to show players. */
    public const PUBLIC_ITEM_METADATA = ['chronotraces', 'active_matter', 'volume'];

    /** Map metadata keys that are safe to show players. */
    public const PUBLIC_MAP_METADATA = ['region', 'region_note', 'variants', 'variant_options', 'facts'];

    public static function visibleTo(?User $user): bool
    {
        return (bool) $user?->canManageContent();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>  $publicKeys
     * @return array<string, mixed>
     */
    public static function filterMetadata(?array $metadata, array $publicKeys, ?User $user): array
    {
        $metadata ??= [];

        if (self::visibleTo($user)) {
            return $metadata;
        }

        $filtered = array_intersect_key($metadata, array_flip($publicKeys));

        // Map facts carry per-fact source links.
        if (isset($filtered['facts']) && is_array($filtered['facts'])) {
            $filtered['facts'] = array_values(array_map(
                fn ($fact) => is_array($fact) ? array_diff_key($fact, ['source_url' => true]) : $fact,
                $filtered['facts'],
            ));
        }

        return $filtered;
    }
}
