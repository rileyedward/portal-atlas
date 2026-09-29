<?php

namespace App\Services\DataExchange;

use App\Enums\ContentStatus;
use App\Enums\MapStatus;
use App\Enums\ObjectiveKind;
use App\Enums\RecipeKind;
use App\Enums\SourceKind;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\LootTable;
use App\Models\Map;
use App\Models\Objective;
use App\Models\Recipe;
use App\Models\Source;
use App\Services\ConfidenceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Imports reference game data (versions, sources, maps, items, recipes,
 * objectives) in the `active-matter-data/v1` format. Upserts by natural key
 * (version string, source name, slug). Never deletes.
 */
class GameDataImporter
{
    public const FORMAT = 'active-matter-data/v1';

    public function __construct(private ConfidenceCalculator $confidence) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, bool $dryRun = false): ImportResult
    {
        $result = new ImportResult($dryRun);

        $validator = Validator::make($payload, [
            'format' => ['required', Rule::in([self::FORMAT])],
            'versions' => ['array'],
            'versions.*.version' => ['required', 'string', 'max:50'],
            'versions.*.name' => ['nullable', 'string', 'max:255'],
            'versions.*.released_at' => ['nullable', 'date'],
            'versions.*.is_current' => ['boolean'],
            'versions.*.notes' => ['nullable', 'string'],
            'versions.*.source_url' => ['nullable', 'url'],
            'sources' => ['array'],
            'sources.*.name' => ['required', 'string', 'max:255'],
            'sources.*.kind' => ['required', Rule::enum(SourceKind::class)],
            'sources.*.url' => ['nullable', 'url'],
            'sources.*.reliability' => ['nullable', 'integer', 'between:0,100'],
            'sources.*.notes' => ['nullable', 'string'],
            'maps' => ['array'],
            'maps.*.slug' => ['required', 'alpha_dash', 'max:100'],
            'maps.*.name' => ['sometimes', 'string', 'max:255'],
            'maps.*.summary' => ['nullable', 'string'],
            'maps.*.description' => ['nullable', 'string'],
            'maps.*.status' => ['nullable', Rule::enum(MapStatus::class)],
            'maps.*.width' => ['nullable', 'integer', 'min:100', 'max:20000'],
            'maps.*.height' => ['nullable', 'integer', 'min:100', 'max:20000'],
            'maps.*.sort_order' => ['nullable', 'integer'],
            'maps.*.version' => ['nullable', 'string'],
            'maps.*.source' => ['nullable', 'string'],
            'maps.*.source_url' => ['nullable', 'url'],
            'maps.*.metadata' => ['nullable', 'array'],
            'item_categories' => ['array'],
            'item_categories.*.slug' => ['required', 'alpha_dash'],
            'item_categories.*.name' => ['required', 'string'],
            'item_categories.*.description' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.slug' => ['required', 'alpha_dash', 'max:150'],
            'items.*.external_ref' => ['nullable', 'string', 'max:160'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.rarity' => ['nullable', 'string', 'max:20'],
            'items.*.value' => ['nullable', 'integer', 'min:0'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.status' => ['nullable', Rule::enum(ContentStatus::class)],
            'items.*.source' => ['nullable', 'string'],
            'items.*.source_url' => ['nullable', 'url'],
            'items.*.source_note' => ['nullable', 'string'],
            'items.*.verified_version' => ['nullable', 'string'],
            'items.*.metadata' => ['nullable', 'array'],
            'loot_tables' => ['array'],
            'loot_tables.*.key' => ['required', 'string', 'max:255'],
            'loot_tables.*.name' => ['required', 'string', 'max:255'],
            'loot_tables.*.source' => ['nullable', 'string'],
            'loot_tables.*.metadata' => ['nullable', 'array'],
            'loot_tables.*.items' => ['array'],
            'loot_tables.*.items.*.item' => ['required', 'string'],
            'loot_tables.*.items.*.chance' => ['nullable', 'numeric', 'between:0,100'],
            'recipes' => ['array'],
            'recipes.*.slug' => ['required', 'alpha_dash'],
            'recipes.*.name' => ['required', 'string'],
            'recipes.*.kind' => ['required', Rule::enum(RecipeKind::class)],
            'recipes.*.station' => ['nullable', 'string'],
            'recipes.*.level' => ['nullable', 'integer'],
            'recipes.*.output' => ['nullable', 'string'],
            'recipes.*.output_quantity' => ['nullable', 'integer', 'min:1'],
            'recipes.*.description' => ['nullable', 'string'],
            'recipes.*.source' => ['nullable', 'string'],
            'recipes.*.source_url' => ['nullable', 'url'],
            'recipes.*.ingredients' => ['array'],
            'recipes.*.ingredients.*.item' => ['required', 'string'],
            'recipes.*.ingredients.*.quantity' => ['required', 'integer', 'min:1'],
            'objectives' => ['array'],
            'objectives.*.slug' => ['required', 'alpha_dash'],
            'objectives.*.name' => ['required', 'string'],
            'objectives.*.kind' => ['required', Rule::enum(ObjectiveKind::class)],
            'objectives.*.map' => ['nullable', 'string'],
            'objectives.*.description' => ['nullable', 'string'],
            'objectives.*.giver' => ['nullable', 'string'],
            'objectives.*.rewards' => ['nullable', 'string'],
            'objectives.*.source' => ['nullable', 'string'],
            'objectives.*.source_url' => ['nullable', 'url'],
            'objectives.*.source_note' => ['nullable', 'string'],
            'objectives.*.items' => ['array'],
            'objectives.*.items.*.item' => ['required', 'string'],
            'objectives.*.items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'objectives.*.items.*.role' => ['nullable', Rule::in(['required', 'reward'])],
        ]);

        if ($validator->fails()) {
            $result->errors = array_values($validator->errors()->all());

            return $result;
        }

        /** @var array<string, list<array<string, mixed>>> $data */
        $data = $validator->validated();

        try {
            DB::transaction(function () use ($data, $result, $dryRun) {
                foreach ($data['versions'] ?? [] as $row) {
                    if (! empty($row['is_current'])) {
                        GameVersion::query()->update(['is_current' => false]);
                    }
                    $this->track($result, GameVersion::updateOrCreate(['version' => $row['version']], $row));
                }
                foreach ($data['sources'] ?? [] as $row) {
                    $row['reliability'] ??= SourceKind::from($row['kind'])->defaultReliability();
                    $this->track($result, Source::updateOrCreate(['name' => $row['name']], $row));
                }

                $versions = GameVersion::pluck('id', 'version');
                $sources = Source::pluck('id', 'name');
                $ref = fn (array $row, string $key, $lookup) => isset($row[$key]) ? ($lookup[$row[$key]] ?? $this->missing($result, $key, $row[$key])) : null;
                // Only touch relation columns the row actually mentions, so partial
                // updates (e.g. adding values to existing items) never null them out.
                $refs = function (array $row, array $columns) use ($ref): array {
                    $out = [];
                    foreach ($columns as $column => [$key, $lookup]) {
                        if (array_key_exists($key, $row)) {
                            $out[$column] = $ref($row, $key, $lookup);
                        }
                    }

                    return $out;
                };

                foreach ($data['maps'] ?? [] as $row) {
                    $map = Map::firstOrNew(['slug' => $row['slug']]);
                    if (! $map->exists && ! isset($row['name'])) {
                        $result->errors[] = "Map \"{$row['slug']}\" does not exist and has no name.";

                        continue;
                    }
                    $map->fill([
                        ...Arr::only($row, ['name', 'summary', 'description', 'status', 'width', 'height', 'sort_order', 'source_url']),
                        ...$refs($row, ['game_version_id' => ['version', $versions], 'source_id' => ['source', $sources]]),
                    ]);
                    if (isset($row['metadata'])) {
                        // Merge so independent datasets can each contribute keys.
                        $map->metadata = array_replace($map->metadata ?? [], $row['metadata']);
                    }
                    $map->save();
                    $this->track($result, $map);
                }
                foreach ($data['item_categories'] ?? [] as $index => $row) {
                    $this->track($result, ItemCategory::updateOrCreate(['slug' => $row['slug']], [...$row, 'sort_order' => $index]));
                }

                $categories = ItemCategory::pluck('id', 'slug');
                foreach ($data['items'] ?? [] as $row) {
                    $item = Item::updateOrCreate(['slug' => $row['slug']], [
                        ...Arr::only($row, ['name', 'external_ref', 'description', 'rarity', 'value', 'weight', 'status', 'source_url', 'source_note', 'metadata']),
                        ...$refs($row, [
                            'item_category_id' => ['category', $categories],
                            'source_id' => ['source', $sources],
                            'verified_version_id' => ['verified_version', $versions],
                        ]),
                    ]);
                    $this->track($result, $item);
                    $this->confidence->refresh($item);
                }

                $items = Item::pluck('id', 'slug');
                $maps = Map::pluck('id', 'slug');
                foreach ($data['loot_tables'] ?? [] as $row) {
                    $table = LootTable::updateOrCreate(['key' => $row['key']], [
                        'name' => $row['name'],
                        'metadata' => $row['metadata'] ?? null,
                        ...$refs($row, ['source_id' => ['source', $sources]]),
                    ]);
                    $this->track($result, $table);
                    $entries = [];
                    foreach ($row['items'] ?? [] as $entry) {
                        isset($items[$entry['item']])
                            ? $entries[$items[$entry['item']]] = ['chance' => $entry['chance'] ?? null]
                            : $this->missing($result, 'item', $entry['item']);
                    }
                    $table->items()->sync($entries);
                }
                foreach ($data['recipes'] ?? [] as $row) {
                    $recipe = Recipe::updateOrCreate(['slug' => $row['slug']], [
                        ...Arr::only($row, ['name', 'kind', 'station', 'level', 'output_quantity', 'description', 'source_url']),
                        'output_item_id' => $ref($row, 'output', $items),
                        'source_id' => $ref($row, 'source', $sources),
                    ]);
                    $this->track($result, $recipe);
                    $ingredients = [];
                    foreach ($row['ingredients'] ?? [] as $ingredient) {
                        isset($items[$ingredient['item']])
                            ? $ingredients[$items[$ingredient['item']]] = ['quantity' => $ingredient['quantity']]
                            : $this->missing($result, 'item', $ingredient['item']);
                    }
                    $recipe->ingredients()->sync($ingredients);
                }
                foreach ($data['objectives'] ?? [] as $row) {
                    $objective = Objective::updateOrCreate(['slug' => $row['slug']], [
                        ...Arr::only($row, ['name', 'kind', 'description', 'giver', 'rewards', 'source_url', 'source_note']),
                        'map_id' => $ref($row, 'map', $maps),
                        'source_id' => $ref($row, 'source', $sources),
                    ]);
                    $this->track($result, $objective);
                    $objectiveItems = [];
                    foreach ($row['items'] ?? [] as $entry) {
                        isset($items[$entry['item']])
                            ? $objectiveItems[$items[$entry['item']]] = ['quantity' => $entry['quantity'] ?? 1, 'role' => $entry['role'] ?? 'required']
                            : $this->missing($result, 'item', $entry['item']);
                    }
                    $objective->items()->sync($objectiveItems);
                    $this->confidence->refresh($objective);
                }

                if ($dryRun || $result->errors !== []) {
                    throw new RollbackImport;
                }
            });
        } catch (RollbackImport) {
            // Dry run or reference errors: nothing persisted.
        } catch (Throwable $e) {
            report($e);
            $result->errors[] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }

    private function track(ImportResult $result, Model $model): void
    {
        match (true) {
            $model->wasRecentlyCreated => $result->created++,
            $model->wasChanged() => $result->updated++,
            default => $result->unchanged++,
        };
    }

    private function missing(ImportResult $result, string $key, mixed $value): null
    {
        $result->errors[] = "Unknown {$key} reference \"{$value}\".";

        return null;
    }
}
