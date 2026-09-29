<?php

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\User;
use App\Services\DataExchange\GameDataImporter;
use App\Services\DataExchange\MapDatasetExporter;
use App\Services\DataExchange\MapDatasetImporter;
use Database\Seeders\MarkerTaxonomySeeder;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => $this->seed(MarkerTaxonomySeeder::class));

test('export then import round-trips a map without duplicating markers', function () {
    $map = Map::factory()->create(['slug' => 'factory']);
    $item = Item::factory()->create(['slug' => 'electronics']);
    $marker = Marker::factory()->for($map)->create(['name' => 'Warehouse']);
    $marker->items()->attach($item);

    $payload = app(MapDatasetExporter::class)->export($map);
    expect($payload['format'])->toBe('active-matter-map/v1')->and($payload['markers'][0]['items'])->toBe(['electronics']);

    $result = app(MapDatasetImporter::class)->import($payload, dryRun: false);

    expect($result->ok())->toBeTrue()
        ->and($result->created)->toBe(0)
        ->and(Marker::count())->toBe(1);
});

test('dry run validates without writing', function () {
    Map::factory()->create(['slug' => 'factory']);

    $result = app(MapDatasetImporter::class)->import([
        'map' => 'factory',
        'markers' => [['type' => 'extraction-point', 'name' => 'South portal', 'x' => 42.18, 'y' => 63.41]],
    ], dryRun: true);

    expect($result->created)->toBe(1)->and(Marker::count())->toBe(0);
});

test('invalid references abort the whole import', function () {
    Map::factory()->create(['slug' => 'factory']);

    $result = app(MapDatasetImporter::class)->import([
        'map' => 'factory',
        'markers' => [
            ['type' => 'extraction-point', 'name' => 'Good', 'x' => 1, 'y' => 1],
            ['type' => 'not-a-type', 'name' => 'Bad', 'x' => 1, 'y' => 1],
        ],
    ], dryRun: false);

    expect($result->ok())->toBeFalse()->and(Marker::count())->toBe(0);
});

test('markers can be imported without coordinates as unplaced', function () {
    Map::factory()->create(['slug' => 'factory']);

    $result = app(MapDatasetImporter::class)->import([
        'map' => 'factory',
        'markers' => [['type' => 'landmark', 'name' => 'Water tower']],
    ], dryRun: false);

    expect($result->ok())->toBeTrue()->and(Marker::sole()->isPlaced())->toBeFalse();
});

test('game data import upserts items, recipes and objectives', function () {
    $payload = [
        'format' => GameDataImporter::FORMAT,
        'versions' => [['version' => '0.4.0.156', 'is_current' => true]],
        'sources' => [['name' => 'Official news', 'kind' => 'official', 'url' => 'https://example.com']],
        'item_categories' => [['slug' => 'electronics', 'name' => 'Electronics']],
        'items' => [['slug' => 'radio', 'name' => 'Radio', 'category' => 'electronics', 'rarity' => 'Common', 'source' => 'Official news']],
        'recipes' => [['slug' => 'test-upgrade', 'name' => 'Test upgrade', 'kind' => 'upgrade', 'ingredients' => [['item' => 'radio', 'quantity' => 2]]]],
        'objectives' => [['slug' => 'collect', 'name' => 'Collect', 'kind' => 'objective', 'items' => [['item' => 'radio', 'quantity' => 3]]]],
    ];

    $first = app(GameDataImporter::class)->import($payload);
    $second = app(GameDataImporter::class)->import($payload);

    expect($first->ok())->toBeTrue()
        ->and($second->created)->toBe(0)
        ->and(Item::sole()->usedInRecipes()->sole()->pivot->quantity)->toBe(2);
});

test('admins can import via upload with a dry run by default', function () {
    $admin = User::factory()->admin()->create();
    Map::factory()->create(['slug' => 'factory']);
    $file = UploadedFile::fake()->createWithContent('factory.json', json_encode([
        'map' => 'factory',
        'markers' => [['type' => 'landmark', 'name' => 'Water tower', 'x' => 5, 'y' => 5]],
    ]));

    $this->actingAs($admin)->post(route('admin.data.import'), ['file' => $file])->assertRedirect();

    expect(Marker::count())->toBe(0);
});

test('admins can download an export', function () {
    $admin = User::factory()->admin()->create();
    $map = Map::factory()->create();

    $this->actingAs($admin)->get(route('admin.data.export', $map))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$map->slug.'-markers.json"')
        ->assertJsonPath('map', $map->slug);
});
