<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Map;
use App\Models\Objective;
use App\Support\PublicCache;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(PublicCache $cache): Response
    {
        $xml = $cache->remember('sitemap.xml', function () {
            $urls = collect([route('home'), route('items.index'), route('objectives.index'), route('planner.show'), route('about')])
                ->map(fn (string $url) => $this->entry($url));

            Map::query()->published()->get(['slug', 'updated_at'])->each(function (Map $map) use ($urls) {
                $urls->push($this->entry(route('maps.show', $map), $map->updated_at), $this->entry(route('extractions.show', $map), $map->updated_at));
            });
            Item::query()->published()->get(['slug', 'updated_at'])->each(fn (Item $item) => $urls->push($this->entry(route('items.show', $item), $item->updated_at)));
            Objective::query()->published()->get(['slug', 'updated_at'])->each(fn (Objective $o) => $urls->push($this->entry(route('objectives.show', $o), $o->updated_at)));

            $body = $urls->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function entry(string $url, ?CarbonInterface $modified = null): string
    {
        return '<url><loc>'.e($url).'</loc>'.($modified ? '<lastmod>'.$modified->toDateString().'</lastmod>' : '').'</url>';
    }
}
