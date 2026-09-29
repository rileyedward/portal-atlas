<?php

use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use Database\Seeders\MarkerTaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(MarkerTaxonomySeeder::class));

test('anonymous players can open a published map', function () {
    $map = Map::factory()->create();
    $type = MarkerType::where('slug', 'landmark')->first();
    Marker::factory()->for($map)->create(['name' => 'Water tower', 'marker_type_id' => $type->id]);
    Marker::factory()->for($map)->draft()->create(['name' => 'Secret draft', 'marker_type_id' => $type->id]);

    $this->get(route('maps.show', $map))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maps/Show')
            ->where('map.slug', $map->slug)
            ->has('markers', 1)
            ->where('markers.0.name', 'Water tower')
            ->where('personal', null)
            ->has('categories', 5));
});

test('draft maps are hidden from players', function () {
    $map = Map::factory()->draft()->create();

    $this->get(route('maps.show', $map))->assertForbidden();
});

test('home lists published maps only', function () {
    Map::factory()->create(['name' => 'Factory']);
    Map::factory()->draft()->create(['name' => 'Unreleased']);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Home')
        ->has('maps', 1)
        ->where('maps.0.name', 'Factory'));
});

test('marker details expose unknowns as null instead of guesses', function () {
    $marker = Marker::factory()->create(['description' => null]);

    $this->getJson(route('api.markers.show', $marker))
        ->assertOk()
        ->assertJsonPath('data.description', null)
        ->assertJsonPath('data.source', null)
        ->assertJsonPath('data.last_verified_at', null)
        ->assertJsonPath('data.confidence.label', 'Unverified');
});

test('draft marker details are not public', function () {
    $marker = Marker::factory()->draft()->create();

    $this->getJson(route('api.markers.show', $marker))->assertForbidden();
});

test('extraction page lists only extraction markers', function () {
    $map = Map::factory()->create();
    $extractType = MarkerType::where('slug', 'extraction-point')->first();
    $lootType = MarkerType::where('slug', 'loot-location')->first();
    Marker::factory()->for($map)->create(['marker_type_id' => $extractType->id, 'name' => 'South portal']);
    Marker::factory()->for($map)->create(['marker_type_id' => $lootType->id, 'name' => 'Crate']);

    $this->get(route('extractions.show', $map))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('maps/Extractions')
        ->has('extracts', 1)
        ->where('extracts.0.name', 'South portal'));
});

test('sitemap includes public pages', function () {
    $map = Map::factory()->create();

    $this->get(route('sitemap'))->assertOk()->assertSee(route('maps.show', $map), false);
});

test('robots.txt blocks private areas and points at the sitemap', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
});
