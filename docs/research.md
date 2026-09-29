# Research

_Compiled 2026-09-29. Full notes, quotes and per-fact source URLs are in [research-notes.md](research-notes.md); this document summarises what matters for the product._

## 1. The game

| Fact                          | Value                                                    | Source                                          | Confidence |
| ----------------------------- | -------------------------------------------------------- | ----------------------------------------------- | ---------- |
| Developer                     | Matter Team                                              | Steam page, gaijinent.com, official site footer | High       |
| Publisher                     | Gaijin Entertainment (Steam: "Gaijin Network Ltd")       | Steam page                                      | High       |
| Early version                 | 9 Sep 2025 (PC, Gaijin launcher)                         | activematter.game/en/news/28                    | High       |
| Full release                  | 15 Sep 2026 — PC (Steam + Gaijin), PS5, Xbox Series X\|S | news/318                                        | High       |
| Latest major update           | "250 Shades of Liberty" (0.4.0.x, 27 Aug 2026)           | news/291                                        | High       |
| Latest build at research time | 0.4.0.156 (26 Sep 2026)                                  | news/339                                        | High       |

Builds ship every 1–3 days. The app never hard-codes the current version: it lives in `game_versions` and is edited from the admin panel.

## 2. Maps (raids)

Thirteen base raids are live and are seeded as maps: Shegolskoe, Ozernoe, Factory, Dogorsk, Cargo Port, Dam, Park, Military Base, Scrapyard, Headquarters, Airport, Downtown and Gigastructure. Variants and limited-time "Overtime" raids (Overgrowth, Distortion, Hive, Bloodbath, Darkness, Deep Cover, Unstable Zone, Collapse, Timeline Collision, Firestorm…) are recorded as map metadata rather than separate maps. Singularity Point (tutorial) and Epicenter (unreleased) are not seeded.

Open questions: whether Headquarters is in Africa, and whether Military Base and Scrapyard are on Dalniy Island (only the community wiki says so). Both are recorded as notes on the map, not as facts.

## 3. Key finding: no source publishes positions

**No official or community source publishes coordinates** for points of interest, extraction portals, loot or enemies. Sources describe places in words ("the southern extraction portal on a Euclid anomaly", "Hangar 84 to the southwest"). Official text never names extraction points; it only says "extraction portal", "final portal" or "dynamic extraction points".

Consequences for the product:

- Places we know exist are stored as **unplaced markers** (`x`/`y` = null) with their source, shown in a "Known, not yet positioned" list.
- Editors with access to the game position them from their own play in the admin map editor. Nothing is placed from guesswork.
- Relative directions ("north of X") are kept in the description text, never turned into coordinates.

## 4. Mechanics relevant to the data model

- **Extraction:** through extraction portals. The zone collapses in phases, and a final extraction point can appear after the collapse. Dogorsk has dynamic extraction points. Some extracts have conditions (Bloodbath needs data cards; Violators are limited to one portal), so markers have a `metadata.conditions` field.
- **Loot tiers:** Common, Uncommon, Rare and Epic. The official text confirms uncommon, rare and Epic; the wiki adds Common. Item tags include Weapon, Civil item, Electronics, Vinyl record and Cassette tape. "Enriched" and "Saturated" items refine for more.
- **Crafting and base:** Shelter, Replicator (replication and fusion), Refiner (refine, recycle, repair), Chronotraces (six types), Chronogenes (fused from monster remains), and Monolith access levels. **No recipe or upgrade input lists are published in any source we found**, so the recipes table is empty until they are verified in game.
- **Objectives:** primary, operative, investigations (multi-stage, with Cases), daily, Priority Raid, collaborative, and contracts (Combat Practice, Vehicle Transfer, Collect and Extract, Specimen Capture…).
- **Threats:** Turned Soldiers, Flowermen, the Alpha Flowerman, Dendroids, Distorted, Mimics, the Alpha Mimic, the Car Mimic, Invisibles, Hellhounds, Shy Girl, Scorches, Devourers, Swarms, Police/SWAT and others. Anomalies include gravity traps, ball lightning, fireballs, fire, tentacles and anomalous flowers.

## 5. Data sources

| Source                                   | Kind           | Reliability we assign | Licence                                                    | Used for                                         |
| ---------------------------------------- | -------------- | --------------------- | ---------------------------------------------------------- | ------------------------------------------------ |
| activematter.game news, patch notes, FAQ | Official       | 95                    | © Gaijin. We use facts only and never copy prose at length | Maps, POIs, mechanics, version history           |
| activematter.wiki.gg                     | Community wiki | 55                    | CC BY-SA 4.0                                               | Item names, categories, rarity; POI descriptions |
| Fandom (EN)                              | Community wiki | 30                    | CC BY-SA                                                   | Corroboration only                               |
| Fandom (RU)                              | Community wiki | 40                    | CC BY-SA                                                   | Corroboration (small interactive maps)           |
| activematterhelp.ru                      | Competitor     | —                     | Unknown                                                    | **Not used** (unknown licence and provenance)    |
| zolotayaikona/active-matter-maps         | GitHub         | —                     | No licence                                                 | **Not used**                                     |

## 6. Competitors and how we differ

- **activematterhelp.ru:** the strongest competitor. It has interactive maps with about 5,600 points, damage calculators and a "where to find" item database. Where its data comes from is unknown, and it gives no sources or confidence for its points.
- **wiki.gg:** detailed text and items, but no interactive maps, and no pages for eight of the thirteen raids.
- **RU Fandom:** five small interactive maps (for example, 11 markers on Shegolskoe).
- **Map Genie:** no coverage (its page returns 404).

**How this app differs:**

- Every data point shows its source, confidence, game version and last-verified date.
- Community reporting and confirmation feed back into confidence.
- "Unknown" is shown as unknown.
- There is an item usage graph ("why keep this?"), a deterministic raid planner and personal tracking.

## 7. Legal

See [map-data-strategy.md](map-data-strategy.md) for the decision. In short:

- The Gaijin Guidelines for Content Creators allow free, public, non-commercial fan content made with screenshots, as long as it adds a significant creative contribution and does not imply endorsement.
- **Prohibited:**
    - datamining (EULA 3.2.9);
    - extracting game assets (Guidelines 1.1.5 and 1.1.8);
    - using the game title or logo to identify our product, or in a domain or subdomain (Guidelines 1.1.7).
- **Unclear:** ToS 4.1 excludes "database creation" from the licence without written permission. This needs a human decision; see [PROJECT_STATUS.md](PROJECT_STATUS.md).

## 8. What is manual and what can be automated

| Data                           | How                                                                                     |
| ------------------------------ | --------------------------------------------------------------------------------------- |
| Current build / versions       | Manual (admin). Could be automated by watching the sequential official news IDs         |
| Maps, POI names                | Manual from official news and the wiki (done for the seed)                              |
| Marker positions               | **Manual only**: editors observe in game and place them in the editor                   |
| Item names, rarity, categories | Semi-automated from the wiki.gg category API (see `database/data/tools/build_items.py`) |
| Recipes, upgrades, item uses   | Manual. No source found                                                                 |
| Loot at locations              | Manual, from in-game observation, with community confirmations                          |
