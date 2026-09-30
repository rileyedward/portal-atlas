<?php

use App\Models\Map;
use App\Models\User;
use Database\Seeders\MarkerTaxonomySeeder;

test('guests are redirected away from admin', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('players cannot access admin', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
});

test('editors can access content admin but not user management', function () {
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($editor)->get(route('admin.items.index'))->assertOk();
    $this->actingAs($editor)->get(route('admin.users.index'))->assertForbidden();
});

test('admins can change roles but not their own', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $player), ['role' => 'editor'])->assertRedirect();
    expect($player->refresh()->role->value)->toBe('editor');

    $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => 'player'])->assertStatus(422);
});

test('role cannot be mass assigned through registration or profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'X', 'email' => $user->email, 'role' => 'admin']);

    expect($user->refresh()->role->value)->toBe('player');
});

test('editors can view draft maps', function () {
    $map = Map::factory()->draft()->create();

    $this->actingAs(User::factory()->editor()->create())->get(route('maps.show', $map))->assertOk();
});

test('every admin page renders', function (string $route) {
    $this->seed(MarkerTaxonomySeeder::class);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route($route))->assertOk();
})->with([
    'admin.dashboard', 'admin.analytics.index', 'admin.maps.index', 'admin.maps.create', 'admin.items.index', 'admin.items.create',
    'admin.objectives.index', 'admin.objectives.create', 'admin.recipes.index', 'admin.recipes.create',
    'admin.reports.index', 'admin.versions.index', 'admin.sources.index', 'admin.taxonomy.index',
    'admin.users.index', 'admin.data.index',
]);
