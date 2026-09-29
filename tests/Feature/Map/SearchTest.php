<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;

test('search finds items with the maps and markers they are found at', function () {
    $factory = Map::factory()->create(['name' => 'Factory', 'slug' => 'factory']);
    $warehouse = Marker::factory()->for($factory)->create(['name' => 'Warehouse']);
    $item = Item::factory()->create(['name' => 'Enriched Electronics', 'slug' => 'enriched-electronics']);
    $item->markers()->attach($warehouse);

    $this->getJson(route('api.search', ['q' => 'enriched electronics']))
        ->assertOk()
        ->assertJsonPath('groups.0.key', 'items')
        ->assertJsonPath('groups.0.results.0.name', 'Enriched Electronics')
        ->assertJsonPath('groups.0.results.0.found_at.0.map.slug', 'factory')
        ->assertJsonPath('groups.0.results.0.found_at.0.markers.0.name', 'Warehouse');
});

test('search requires every word to match and ranks exact matches first', function () {
    Item::factory()->create(['name' => 'Radio parts']);
    Item::factory()->create(['name' => 'Radio']);
    Item::factory()->create(['name' => 'Battery']);

    $response = $this->getJson(route('api.search', ['q' => 'radio']))->assertOk();

    expect(collect($response->json('groups.0.results'))->pluck('name')->all())->toBe(['Radio', 'Radio parts']);
});

test('search never leaks draft content', function () {
    Marker::factory()->draft()->create(['name' => 'Hidden bunker']);
    Objective::factory()->create(['name' => 'Hidden bunker objective', 'status' => 'draft']);
    Marker::factory()->for(Map::factory()->draft())->create(['name' => 'Hidden bunker on draft map']);

    $this->getJson(route('api.search', ['q' => 'hidden bunker']))->assertOk()->assertJsonPath('total', 0);
});

test('search treats wildcard characters literally', function () {
    Item::factory()->create(['name' => 'Battery']);

    $this->getJson(route('api.search', ['q' => '%%']))->assertOk()->assertJsonPath('total', 0);
});

test('searches are recorded anonymously for analytics', function () {
    $this->getJson(route('api.search', ['q' => 'nothing here']))->assertOk();

    $this->assertDatabaseHas('analytics_events', ['name' => 'search', 'term' => 'nothing here', 'result_count' => 0]);
});
