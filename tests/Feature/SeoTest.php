<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\User;
use Database\Seeders\MarkerTaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(MarkerTaxonomySeeder::class);
    // Assert the Blade fallback, which is what crawlers get in production (no SSR server).
    config(['inertia.ssr.enabled' => false]);
});

test('home renders keyword title, description, canonical and structured data server-side', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>Active Matter Interactive Map &amp; Raid Companion - '.config('app.name').'</title>', false)
        ->assertSee('<meta data-inertia="description" name="description" content="Unofficial Active Matter interactive map', false)
        ->assertSee('<link data-inertia="canonical" rel="canonical" href="'.route('home').'">', false)
        ->assertSee('{"@context":"https://schema.org","@type":"WebSite"', false)
        ->assertSee('"alternateName":["Active Matter Interactive Map"', false)
        ->assertDontSee('name="robots"', false);
});

test('map pages get their own title, description and canonical url', function () {
    $map = Map::factory()->create(['name' => 'Factory', 'summary' => 'Dense industrial zone.']);

    $this->get(route('maps.show', ['map' => $map, 'marker' => 5]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Factory Interactive Map – Active Matter')
            ->where('seo.description', 'Dense industrial zone.')
            ->where('seo.noindex', false))
        ->assertSee('<title>Factory Interactive Map – Active Matter - '.config('app.name').'</title>', false)
        ->assertSee('rel="canonical" href="'.route('maps.show', $map).'"', false)
        ->assertDontSee('"@type":"WebSite"', false);
});

test('item pages describe where the item is found', function () {
    $item = Item::factory()->create(['name' => 'Copper wire', 'description' => null]);

    $this->get(route('items.show', $item))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Copper wire – Where to Find It in Active Matter')
            ->where('seo.description', 'Copper wire in Active Matter. No mapped locations yet.'));
});

test('private and auth pages are marked noindex', function () {
    $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex">', false);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('content="noindex"', false);
});

test('sitemap entries carry a last modified date', function () {
    $map = Map::factory()->create();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee('<loc>'.route('maps.show', $map).'</loc><lastmod>'.$map->updated_at->toDateString().'</lastmod>', false);
});
