<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Page meta shared as the `seo` Inertia prop. The root Blade view renders it
 * server-side for crawlers and link previews; SeoHead.vue renders it on client visits.
 */
class Seo
{
    public const DEFAULT_DESCRIPTION = 'Unofficial Active Matter interactive map and raid companion: extraction portals, loot, objectives, threats and an item database with confidence scores.';

    /** Route names that search engines may index. Everything else gets noindex. */
    public const INDEXABLE_ROUTES = ['home', 'maps.show', 'extractions.show', 'items.*', 'objectives.*', 'planner.show', 'about'];

    /**
     * @return array{title: string|null, description: string, url: string, noindex: bool}
     */
    public static function make(?string $title = null, ?string $description = null, bool $noindex = false): array
    {
        return [
            'title' => $title,
            'description' => Str::limit(Str::squish($description ?? self::DEFAULT_DESCRIPTION), 160, '…', preserveWords: true),
            // Canonical: the current URL without its query string.
            'url' => url()->current(),
            'noindex' => $noindex,
        ];
    }

    /**
     * The fallback for pages that don't pass their own `seo` prop.
     *
     * @return array{title: string|null, description: string, url: string, noindex: bool}
     */
    public static function defaults(Request $request): array
    {
        return self::make(noindex: ! $request->routeIs(...self::INDEXABLE_ROUTES));
    }
}
