<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use App\Models\User;
use Database\Seeders\MarkerTaxonomySeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(MarkerTaxonomySeeder::class));

function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('public pages reuse cached data on repeat visits', function (string $route) {
    $map = Map::factory()->create();
    Marker::factory()->for($map)->count(3)->create();
    $item = Item::factory()->create();

    $url = match ($route) {
        'home' => route('home'),
        'maps.show' => route('maps.show', $map),
        'items.index' => route('items.index'),
        'items.show' => route('items.show', $item),
    };

    $first = countQueries(fn () => $this->get($url)->assertOk());
    $second = countQueries(fn () => $this->get($url)->assertOk());

    expect($second)->toBeLessThan($first);
})->with(['home', 'maps.show', 'items.index', 'items.show']);

test('admin marker edits show up on the public map straight away', function () {
    $editor = User::factory()->editor()->create();
    $map = Map::factory()->create();
    $marker = Marker::factory()->for($map)->create(['name' => 'Old name', 'marker_type_id' => MarkerType::where('slug', 'landmark')->value('id')]);

    $this->get(route('maps.show', $map))->assertInertia(fn (Assert $page) => $page->where('markers.0.name', 'Old name'));

    $this->actingAs($editor)
        ->patchJson(route('admin.maps.markers.update', [$map, $marker]), ['name' => 'New name'])
        ->assertOk();
    auth()->logout();

    $this->get(route('maps.show', $map))->assertInertia(fn (Assert $page) => $page->where('markers.0.name', 'New name'));
});

test('player reports refresh cached marker confidence', function () {
    $marker = Marker::factory()->create();

    $this->getJson(route('api.markers.show', $marker))->assertJsonPath('data.confidence.open_reports', 0);

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'marker',
        'subject_id' => $marker->id,
        'type' => 'incorrect_location',
    ])->assertCreated();

    $this->getJson(route('api.markers.show', $marker))->assertJsonPath('data.confidence.open_reports', 1);
});

test('cached marker details keep empty metadata as an object and hide sources from players', function () {
    $marker = Marker::factory()->create(['metadata' => null, 'source_note' => 'internal']);

    foreach ([1, 2] as $visit) {
        $response = $this->getJson(route('api.markers.show', $marker))->assertOk()->assertJsonMissingPath('data.source_note');
        expect($response->getContent())->toContain('"metadata":{}');
    }

    $this->actingAs(User::factory()->editor()->create())
        ->getJson(route('api.markers.show', $marker))
        ->assertJsonPath('data.source_note', 'internal');
});

test('personal map data is never shared between players', function () {
    $map = Map::factory()->create();
    $marker = Marker::factory()->for($map)->create();
    $alice = User::factory()->create();
    $alice->markerStates()->attach($marker->id, ['is_favorite' => true]);

    $this->actingAs($alice)->get(route('maps.show', $map))
        ->assertInertia(fn (Assert $page) => $page->where('personal.favorites', [$marker->id]));

    $this->actingAs(User::factory()->create())->get(route('maps.show', $map))
        ->assertInertia(fn (Assert $page) => $page->where('personal.favorites', []));
});
