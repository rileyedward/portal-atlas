# Data import and export

There are two JSON formats. Both can be imported from **Admin → Import / export** (a dry run by default) or from the CLI:

```bash
php artisan game-data:import path/to/file.json --dry-run   # validate only
php artisan game-data:import path/to/file.json             # apply
```

Imports run in a single transaction. Any error rolls back the whole file. Imports **never delete** anything.

## 1. Map marker dataset: `active-matter-map/v1`

This is exported per map from the admin panel (`GET /admin/data/maps/{slug}/export`).

```json
{
    "format": "active-matter-map/v1",
    "map": "factory",
    "version": "0.4.0.156",
    "markers": [
        {
            "id": 12,
            "type": "extraction-point",
            "name": "Southern extraction portal (Euclid anomaly)",
            "description": "In Malie Chelni, on a Euclid anomaly south of the factory complex.",
            "x": 42.18,
            "y": 63.41,
            "status": "published",
            "source": "Active Matter Wiki (wiki.gg)",
            "source_url": "https://activematter.wiki.gg/wiki/Factory",
            "source_note": "optional quote",
            "verified_version": "0.4.0.156",
            "items": ["fracture-shard"],
            "objectives": ["harbor-anomaly-entrance"],
            "metadata": { "conditions": "Only after the collapse phase." }
        }
    ]
}
```

| Field                 | Rules                                                                 |
| --------------------- | --------------------------------------------------------------------- |
| `map`                 | slug of an existing map (create the map first)                        |
| `type`                | a `marker_types.slug` (see Admin → Marker types)                      |
| `x`, `y`              | 0–100 percent from the top left. **Omit both for an unplaced marker** |
| `geometry`            | optional `[[x, y], …]` for areas and paths                            |
| `status`              | draft, published, hidden or removed (default published)               |
| `source`              | a `sources.name`. Unknown names produce a warning and are ignored     |
| `items`, `objectives` | slugs; unknown slugs are **errors**                                   |
| `confidence_override` | 0–100, optional                                                       |

**How rows are matched:** an `id` that belongs to the target map is updated. Otherwise the importer looks for an existing marker with the same type and name on that map. Otherwise a new marker is created. A soft-deleted marker matched by id is restored.

## 2. Reference game data: `active-matter-data/v1`

Keys, all optional: `versions`, `sources`, `maps`, `item_categories`, `items`, `recipes` and `objectives`. Rows are upserted by natural key (version string, source name, or slug). The following example is illustrative; it is not real game data.

```json
{
    "format": "active-matter-data/v1",
    "items": [
        {
            "slug": "example-item",
            "name": "Example item",
            "category": "electronics",
            "rarity": "Common",
            "source": "Active Matter Wiki (wiki.gg)",
            "source_url": "https://…"
        }
    ],
    "recipes": [
        {
            "slug": "example-upgrade",
            "name": "Example upgrade",
            "kind": "upgrade",
            "station": "…",
            "ingredients": [{ "item": "example-item", "quantity": 2 }]
        }
    ],
    "objectives": [
        {
            "slug": "example-objective",
            "name": "…",
            "kind": "objective",
            "map": "factory",
            "items": [
                { "item": "example-item", "quantity": 3, "role": "required" }
            ]
        }
    ]
}
```

## Seed datasets (`database/data`)

| File              | Contents                                                                                  | Built by                                                 |
| ----------------- | ----------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `reference.json`  | versions, sources, 13 maps with sourced field notes                                       | `tools/build_reference.py`                               |
| `items.json`      | 607 items: names, categories and rarity from wiki.gg (CC BY-SA 4.0) and official listings | `tools/build_items.py` (parses `docs/research-notes.md`) |
| `objectives.json` | 3 objectives with sources                                                                 | `tools/build_reference.py`                               |
| `maps/*.json`     | 85 **unplaced** markers (every one cites a source)                                        | `tools/build_reference.py`                               |

`php artisan db:seed` (the `GameDataSeeder`) loads them in order. Re-running it is idempotent.

To regenerate after editing the scripts or the research notes:

```bash
python3 database/data/tools/build_items.py
python3 database/data/tools/build_reference.py
php artisan db:seed --class=GameDataSeeder
```

## Contribution workflow

1. Export a map.
2. Edit it: add `x`/`y` to unplaced markers, or add new sourced markers.
3. Import it with a dry run and read the summary.
4. Import it for real.
5. Commit the updated file under `database/data/maps/` so that the dataset is versioned in git.

## Third-party dataset: activematterhelp.ru

At the project maintainer's request (accepting the legal risk described in [research.md](research.md)), positions, zones, loot pools and drop chances are imported from the fan site [activematterhelp.ru](https://activematterhelp.ru/maps). The site is © Active Matter Help and grants no licence, and its coordinates look like game-world positions, so they may be datamined. Every imported record is attributed to the source **Active Matter Help (activematterhelp.ru)** (reliability 70), so imported markers show as Medium confidence and "never verified" until someone confirms them in game.

```bash
node database/data/tools/fetch_activematterhelp.mjs      # downloads the site's public data bundles -> storage/app/private/activematterhelp/raw.json (git-ignored)
python3 database/data/tools/build_activematterhelp.py    # converts to database/data/activematterhelp/{reference.json,maps/*.json}
php artisan db:seed --class=GameDataSeeder               # idempotent: updates in place by external_ref
```

What the converter does:

| Their data                                                       | Ours                                                                                                                                                                                                                                 |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 13 raid locations with world-coordinate bounds                   | per-map `metadata.calibration`, converted to 0–100% coordinates (north up); map width and height are set to 2 px per metre                                                                                                           |
| 5,583 markers (35 types)                                         | markers with a mapped `type` (new types: portal, vehicle spawn, locked door, final extraction, tier-2/3 containers, safe, medical, ammo box, artifact, documents, key spawn, interactive, puzzle, boss, monster spawn zone)          |
| 380 region polygons                                              | `area` markers with `geometry`                                                                                                                                                                                                       |
| raid "mods"                                                      | marker `variant` (regular, overgrowth, hive, bloodbath, darkness, deep_cover, timeline, escape, br = Unstable Zone, purge = Support Protocol, mothman, collapse…), plus `metadata.variant_options` on the map for the variant picker |
| 1,470 loot tables                                                | `loot_tables` and `item_loot_table` (chance %). Tables whose title names a raid get `metadata.map`                                                                                                                                   |
| ~1,400 loot items (English names, rarity, credits, chronotraces) | matched to existing items by name, otherwise created (`external_ref` `amh:<name>`); credits are stored as `value`                                                                                                                    |
| keys                                                             | key names shown in marker conditions ("Requires: …")                                                                                                                                                                                 |
| Russian names                                                    | English names from the site's own translation data (two strings translated by hand)                                                                                                                                                  |

Not imported: per-marker screenshots and videos, and the Park bunker interior (it uses a separate coordinate space).

### Map base images

At the maintainer's request, each raid's base image is stitched from activematterhelp.ru's map tiles (game imagery, © Gaijin Entertainment; attributed on the map) and cropped to that raid's calibration box, so it lines up with the imported markers:

```bash
python3 database/data/tools/build_map_images.py   # downloads ~370 tiles (cached), writes storage/app/public/maps/<slug>.webp + database/data/activematterhelp/map-images.json
php artisan storage:link                          # once
php artisan db:seed --class=GameDataSeeder        # attaches images listed in the manifest (skips missing files)
```

The images are **not committed** (they are in `storage/`, and total about 21 MB). On a new machine or in production, run the script, or upload the files to the public disk. Gigastructure has no tiles and keeps the schematic grid.

### Format additions

- `active-matter-map/v1` markers accept `external_ref` (authoritative match key; rows with it never fall back to type-and-name matching), `variant` and `loot_table` (a loot table key).
- `active-matter-data/v1` accepts `loot_tables: [{key, name, source?, metadata?, items: [{item, chance?}]}]` and `items[].external_ref`. Item and map rows may be partial: relation columns (category, source, version) are only changed when the row names them, and map `metadata` is merged rather than replaced.
