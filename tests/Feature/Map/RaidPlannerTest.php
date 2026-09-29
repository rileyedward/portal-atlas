<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use Database\Seeders\MarkerTaxonomySeeder;

beforeEach(fn () => $this->seed(MarkerTaxonomySeeder::class));

test('planner builds a route through item locations and ends at the nearest extract', function () {
    $map = Map::factory()->create(['slug' => 'factory']);
    $loot = MarkerType::where('slug', 'loot-location')->first();
    $extract = MarkerType::where('slug', 'extraction-point')->first();
    $enemy = MarkerType::where('slug', 'enemy')->first();

    $electronics = Item::factory()->create();
    $battery = Item::factory()->create();
    $unfindable = Item::factory()->create();

    $far = Marker::factory()->for($map)->create(['marker_type_id' => $loot->id, 'name' => 'Far', 'x' => 90, 'y' => 90]);
    $near = Marker::factory()->for($map)->create(['marker_type_id' => $loot->id, 'name' => 'Near', 'x' => 12, 'y' => 12]);
    $far->items()->attach($battery);
    $near->items()->attach($electronics);
    Marker::factory()->for($map)->create(['marker_type_id' => $extract->id, 'name' => 'North portal', 'x' => 5, 'y' => 5]);
    Marker::factory()->for($map)->create(['marker_type_id' => $extract->id, 'name' => 'South portal', 'x' => 95, 'y' => 95]);
    Marker::factory()->for($map)->create(['marker_type_id' => $enemy->id, 'name' => 'Camp', 'x' => 92, 'y' => 92]);
    // Unplaced markers are never routed to.
    Marker::factory()->for($map)->create(['marker_type_id' => $loot->id, 'name' => 'Unknown spot', 'x' => null, 'y' => null])->items()->attach($unfindable);

    $this->postJson(route('api.planner.plan'), [
        'map' => 'factory',
        'items' => [$electronics->id, $battery->id, $unfindable->id],
        'start' => ['x' => 10, 'y' => 10],
    ])->assertOk()
        ->assertJsonPath('stops.0.marker.name', 'Near')
        ->assertJsonPath('stops.1.marker.name', 'Far')
        ->assertJsonPath('stops.1.risks.0', 'Enemy: Camp')
        ->assertJsonPath('extract.name', 'South portal')
        ->assertJsonPath('missing_items', [$unfindable->id]);
});
