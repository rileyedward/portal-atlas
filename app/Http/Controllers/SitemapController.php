<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Map;
use App\Models\Objective;
use App\Support\PublicCache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(PublicCache $cache): Response
    {
        $xml = $cache->remember('sitemap.xml', function () {
            $urls = collect([route('home'), route('items.index'), route('objectives.index'), route('planner.show'), route('about')]);

            Map::query()->published()->get(['slug'])->each(function (Map $map) use ($urls) {
                $urls->push(route('maps.show', $map), route('extractions.show', $map));
            });
            Item::query()->published()->get(['slug'])->each(fn (Item $item) => $urls->push(route('items.show', $item)));
            Objective::query()->published()->get(['slug'])->each(fn (Objective $o) => $urls->push(route('objectives.show', $o)));

            $body = $urls->map(fn (string $url) => '<url><loc>'.e($url).'</loc></url>')->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
