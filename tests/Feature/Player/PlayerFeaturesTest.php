<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\MapNote;
use App\Models\Marker;
use App\Models\RaidRoute;
use App\Models\Recipe;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('notes are private to their owner', function () {
    $map = Map::factory()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $response = $this->actingAs($owner)->postJson(route('api.notes.store'), [
        'map_id' => $map->id, 'x' => 12.5, 'y' => 40, 'title' => 'PvP here constantly',
    ])->assertCreated();
    $note = MapNote::findOrFail($response->json('id'));

    $this->actingAs($other)->get(route('maps.show', $map))
        ->assertInertia(fn (Assert $page) => $page->has('personal.notes', 0));
    $this->actingAs($other)->patchJson(route('api.notes.update', $note), ['title' => 'mine now'])->assertForbidden();
    $this->actingAs($other)->deleteJson(route('api.notes.destroy', $note))->assertForbidden();

    $this->actingAs($owner)->get(route('maps.show', $map))
        ->assertInertia(fn (Assert $page) => $page->has('personal.notes', 1));
});

test('guests cannot create notes', function () {
    $this->postJson(route('api.notes.store'), ['map_id' => 1, 'x' => 1, 'y' => 1, 'title' => 'x'])->assertUnauthorized();
});

test('players can favorite and discover markers', function () {
    $marker = Marker::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->putJson(route('api.markers.state', $marker), ['is_favorite' => true])
        ->assertOk()->assertJsonPath('is_favorite', true)->assertJsonPath('discovered', false);
    $this->actingAs($user)->putJson(route('api.markers.state', $marker), ['discovered' => true])
        ->assertOk()->assertJsonPath('is_favorite', true)->assertJsonPath('discovered', true);
});

test('players can save, share and delete raid routes', function () {
    $map = Map::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.routes.store'), [
        'map_id' => $map->id,
        'name' => 'Solo electronics',
        'is_public' => true,
        'points' => [['x' => 10, 'y' => 20, 'label' => 'Spawn'], ['x' => 50, 'y' => 60, 'label' => '<b>Extract</b>']],
    ])->assertCreated();

    $route = RaidRoute::findOrFail($response->json('id'));
    expect($route->points[1]['label'])->toBe('Extract');

    // Anyone with the share link can view a public route.
    $this->get(route('maps.show', ['map' => $map, 'route' => $route->share_token]))
        ->assertInertia(fn (Assert $page) => $page->where('sharedRoute.name', 'Solo electronics'));

    $this->actingAs(User::factory()->create())->deleteJson(route('api.routes.destroy', $route))->assertForbidden();
    $this->actingAs($user)->deleteJson(route('api.routes.destroy', $route))->assertNoContent();
});

test('private routes are not visible through share links', function () {
    $route = RaidRoute::factory()->create(['is_public' => false, 'share_token' => 'abc123']);

    $this->get(route('maps.show', ['map' => $route->map, 'route' => 'abc123']))
        ->assertInertia(fn (Assert $page) => $page->where('sharedRoute', null));
});

test('routes need at least two points', function () {
    $map = Map::factory()->create();

    $this->actingAs(User::factory()->create())->postJson(route('api.routes.store'), [
        'map_id' => $map->id, 'name' => 'x', 'points' => [['x' => 1, 'y' => 1]],
    ])->assertUnprocessable();
});

test('keep advisor says keep when the item is used by an upgrade', function () {
    $item = Item::factory()->create(['name' => 'Electronics']);
    $recipe = Recipe::factory()->create(['kind' => 'upgrade', 'name' => 'Workshop Level 2']);
    $recipe->ingredients()->attach($item, ['quantity' => 3]);

    $this->get(route('items.show', $item))->assertInertia(fn (Assert $page) => $page
        ->component('items/Show')
        ->where('advice.verdict', 'keep')
        ->where('advice.reasons.0', 'Upgrade: Workshop Level 2 (×3)')
        ->has('usedIn', 1));
});

test('keep advisor reports unknown instead of guessing sell', function () {
    $item = Item::factory()->create();

    $this->get(route('items.show', $item))->assertInertia(fn (Assert $page) => $page->where('advice.verdict', 'unknown'));
});

test('tracking needs raises keep priority and goals add ingredients', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create();
    $recipe = Recipe::factory()->create();
    $recipe->ingredients()->attach($item, ['quantity' => 2]);

    $this->actingAs($user)->postJson(route('api.goals.store', $recipe))->assertOk();
    $this->actingAs($user)->putJson(route('api.tracked-items.update', $item), ['quantity_owned' => 1])
        ->assertOk()
        ->assertJsonPath('advice.verdict', 'keep')
        ->assertJsonPath('advice.priority', 'high')
        ->assertJsonPath('advice.needed', 2)
        ->assertJsonPath('advice.owned', 1);

    $this->actingAs($user)->get(route('me.items'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('tracked', 1));
});
