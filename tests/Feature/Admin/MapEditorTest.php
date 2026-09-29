<?php

use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use App\Models\User;
use Database\Seeders\MarkerTaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(MarkerTaxonomySeeder::class);
    $this->editor = User::factory()->editor()->create();
    $this->map = Map::factory()->create();
});

test('editor page includes drafts and unplaced markers', function () {
    Marker::factory()->for($this->map)->draft()->create();
    Marker::factory()->for($this->map)->create(['x' => null, 'y' => null]);

    $this->actingAs($this->editor)->get(route('admin.maps.editor', $this->map))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/maps/Editor')->has('markers', 2));
});

test('editors can create a marker by clicking the map', function () {
    $type = MarkerType::where('slug', 'extraction-point')->first();
    $item = Item::factory()->create();

    $this->actingAs($this->editor)->postJson(route('admin.maps.markers.store', $this->map), [
        'marker_type_id' => $type->id,
        'name' => '<script>x</script>South portal',
        'x' => 42.181234,
        'y' => 63.41,
        'items' => [['id' => $item->id, 'likelihood' => 'common']],
    ])->assertCreated()->assertJsonPath('name', 'xSouth portal')->assertJsonPath('x', 42.1812);

    $marker = Marker::sole();
    expect($marker->created_by)->toBe($this->editor->id)
        ->and($marker->items()->first()->id)->toBe($item->id);
});

test('coordinates outside the map are rejected', function () {
    $type = MarkerType::first();

    $this->actingAs($this->editor)->postJson(route('admin.maps.markers.store', $this->map), [
        'marker_type_id' => $type->id, 'name' => 'Out', 'x' => 120, 'y' => -3,
    ])->assertUnprocessable()->assertJsonValidationErrors(['x', 'y']);
});

test('editors can move, duplicate and delete markers', function () {
    $marker = Marker::factory()->for($this->map)->create(['x' => 10, 'y' => 10]);

    $this->actingAs($this->editor)->patchJson(route('admin.maps.markers.update', [$this->map, $marker]), ['x' => 20, 'y' => 30])
        ->assertOk()->assertJsonPath('x', 20);

    $this->actingAs($this->editor)->postJson(route('admin.maps.markers.duplicate', [$this->map, $marker]))
        ->assertCreated()->assertJsonPath('name', $marker->name.' (copy)');

    $this->actingAs($this->editor)->deleteJson(route('admin.maps.markers.destroy', [$this->map, $marker]))->assertNoContent();
    $this->assertSoftDeleted($marker);
});

test('markers cannot be edited through another map', function () {
    $marker = Marker::factory()->create();

    $this->actingAs($this->editor)->patchJson(route('admin.maps.markers.update', [$this->map, $marker]), ['x' => 1, 'y' => 1])
        ->assertNotFound();
});

test('players cannot use the marker editor api', function () {
    $marker = Marker::factory()->for($this->map)->create();

    $this->actingAs(User::factory()->create())->patchJson(route('admin.maps.markers.update', [$this->map, $marker]), ['x' => 1, 'y' => 1])
        ->assertForbidden();
});

test('admin verification marks the marker verified for the current version', function () {
    $version = GameVersion::factory()->current()->create();
    $marker = Marker::factory()->for($this->map)->create();

    $this->actingAs($this->editor)->postJson(route('admin.maps.markers.verify', [$this->map, $marker]))->assertOk();

    expect($marker->refresh()->verified_version_id)->toBe($version->id)->and($marker->last_verified_at)->not->toBeNull();
});
