<?php

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LootTable;
use App\Models\Map;
use App\Models\Marker;
use App\Models\MarkerType;
use App\Services\DataExchange\GameDataImporter;
use App\Services\DataExchange\MapDatasetImporter;
use App\Services\ItemLocator;
use Database\Seeders\MarkerTaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(MarkerTaxonomySeeder::class));

function importLootSetup(): void
{
    Map::factory()->create(['slug' => 'factory', 'name' => 'Factory']);
    $result = app(GameDataImporter::class)->import([
        'format' => GameDataImporter::FORMAT,
        'items' => [['slug' => 'lucky-quarter', 'name' => 'Lucky quarter', 'rarity' => 'Epic']],
        'loot_tables' => [['key' => 'payphone', 'name' => 'Payphone', 'items' => [['item' => 'lucky-quarter', 'chance' => 3.1]]]],
    ]);
    expect($result->ok())->toBeTrue();
}

test('map datasets link loot tables and variants and re-import by external ref', function () {
    importLootSetup();
    $payload = [
        'map' => 'factory',
        'markers' => [
            ['external_ref' => 'ext:1', 'type' => 'interactive', 'name' => 'Payphone', 'x' => 10, 'y' => 10, 'loot_table' => 'payphone', 'variant' => 'regular'],
            // Same generic name, different spot: must not be merged with the first.
            ['external_ref' => 'ext:2', 'type' => 'interactive', 'name' => 'Payphone', 'x' => 20, 'y' => 20, 'loot_table' => 'payphone', 'variant' => 'hive'],
        ],
    ];

    expect(app(MapDatasetImporter::class)->import($payload, dryRun: false)->created)->toBe(2);

    $payload['markers'][0]['x'] = 11;
    $second = app(MapDatasetImporter::class)->import($payload, dryRun: false);

    expect($second->created)->toBe(0)
        ->and($second->updated)->toBe(1)
        ->and(Marker::count())->toBe(2)
        ->and(Marker::where('external_ref', 'ext:1')->sole()->x)->toBe(11.0)
        ->and(Marker::where('external_ref', 'ext:2')->sole()->variant)->toBe('hive')
        ->and(Marker::first()->lootTable->key)->toBe('payphone');
});

test('partial item updates never null out existing relations', function () {
    $category = ItemCategory::factory()->create(['slug' => 'coins']);
    Item::factory()->create(['slug' => 'quarter', 'item_category_id' => $category->id, 'value' => null]);

    app(GameDataImporter::class)->import([
        'format' => GameDataImporter::FORMAT,
        'items' => [['slug' => 'quarter', 'name' => 'Quarter', 'value' => 15]],
    ]);

    $item = Item::where('slug', 'quarter')->sole();
    expect($item->value)->toBe(15)->and($item->item_category_id)->toBe($category->id);
});

test('map metadata is merged, not replaced', function () {
    Map::factory()->create(['slug' => 'factory', 'metadata' => ['facts' => [['text' => 'keep me']]]]);

    app(GameDataImporter::class)->import([
        'format' => GameDataImporter::FORMAT,
        'maps' => [['slug' => 'factory', 'metadata' => ['variant_options' => [['key' => 'regular', 'label' => 'Regular', 'count' => 1]]]]],
    ]);

    $map = Map::where('slug', 'factory')->sole();
    expect($map->metadata['facts'][0]['text'])->toBe('keep me')
        ->and($map->metadata['variant_options'][0]['key'])->toBe('regular');
});

test('item locator finds items through loot tables and aggregates identical spots', function () {
    importLootSetup();
    $table = LootTable::where('key', 'payphone')->sole();
    $type = MarkerType::where('slug', 'interactive')->sole();
    $map = Map::where('slug', 'factory')->sole();
    Marker::factory()->count(3)->for($map)->create(['name' => 'Payphone', 'marker_type_id' => $type->id, 'loot_table_id' => $table->id]);
    Marker::factory()->for($map)->draft()->create(['name' => 'Payphone', 'marker_type_id' => $type->id, 'loot_table_id' => $table->id]);

    $places = app(ItemLocator::class)->locate(Item::where('slug', 'lucky-quarter')->sole());

    expect($places)->toHaveCount(1)
        ->and($places[0]['map']['slug'])->toBe('factory')
        ->and($places[0]['markers'])->toHaveCount(1)
        ->and($places[0]['markers'][0]['count'])->toBe(3)
        ->and($places[0]['markers'][0]['chance'])->toBe(3.1);

    $this->get(route('items.show', 'lucky-quarter'))->assertInertia(fn (Assert $page) => $page
        ->where('foundAt.0.markers.0.count', 3)
        ->where('lootPools.0.name', 'Payphone')
        ->where('lootPools.0.spots', 3));

    $this->getJson(route('api.search', ['q' => 'lucky']))->assertJsonPath('groups.0.results.0.found_at.0.markers.0.count', 3);
});

test('marker details include the loot pool with chances', function () {
    importLootSetup();
    $marker = Marker::factory()->for(Map::where('slug', 'factory')->sole())->create([
        'loot_table_id' => LootTable::sole()->id,
        'variant' => 'hive',
    ]);

    $this->getJson(route('api.markers.show', $marker))
        ->assertJsonPath('data.variant', 'hive')
        ->assertJsonPath('data.loot_table.name', 'Payphone')
        ->assertJsonPath('data.loot_table.items.0.chance', 3.1);
});

test('planner uses loot tables and respects the raid variant', function () {
    importLootSetup();
    $map = Map::where('slug', 'factory')->sole();
    $table = LootTable::sole();
    $type = MarkerType::where('slug', 'interactive')->sole();
    Marker::factory()->for($map)->create(['name' => 'Hive phone', 'marker_type_id' => $type->id, 'loot_table_id' => $table->id, 'variant' => 'hive', 'x' => 10, 'y' => 10]);
    $item = Item::where('slug', 'lucky-quarter')->sole();

    $this->postJson(route('api.planner.plan'), ['map' => 'factory', 'items' => [$item->id], 'variant' => 'regular'])
        ->assertOk()->assertJsonPath('missing_items', [$item->id]);

    $this->postJson(route('api.planner.plan'), ['map' => 'factory', 'items' => [$item->id], 'variant' => 'hive'])
        ->assertOk()->assertJsonPath('stops.0.marker.name', 'Hive phone');
});
