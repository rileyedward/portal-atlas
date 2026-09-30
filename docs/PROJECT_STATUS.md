# Project status

_As of 2026-09-29. Tracking game build 0.4.0.156._

## TL;DR

The application is built and working end to end: maps, search, filters, marker details, admin editor, moderation, import and export, player features, planner and SEO. Every automated gate passes.

**Update (2026-09-29): most positions are now imported from activematterhelp.ru** (5,952 markers and zones on 12 maps, with loot pools and drop chances). This was done at the maintainer's request, with the legal risk accepted. The data is unverified until confirmed in game. Twelve raids now have base map images built from the same site's tiles (Gigastructure still uses the grid). The original note below is kept for history.

**The data was honest but thin on positions.** No public source gives coordinates, so all 85 seeded map locations are _unplaced_: they are listed and searchable, but not drawn on the map. Turning this into a genuinely raid-ready map now needs **a person with the game**:

1. Trace an original base map.
2. Position markers in the editor (roughly an evening per map).

## Needs a human decision

| #   | Decision                                                                                                                                                   | Why it matters                                                                                                                                        |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Legal:** email contentpartners@gaijin.net about ToS 4.1 ("database creation" is excluded from the licence without written permission) and about fan maps | Publishing an item/location database may need written consent. Unresolved                                                                             |
| 2   | **Brand and domain:** keep "Portal Atlas" or pick another neutral name                                                                                     | Guidelines 1.1.7 forbid the game title in product names and domains. The project folder and repo name can stay; the public brand cannot use the title |
| 3   | **Who verifies data in game**                                                                                                                              | Positions, loot-at-location and recipes can only come from gameplay                                                                                   |

## Current features

- **Player feedback:** a global feedback form (floating button, footer link, contextual flag icons, "suggest position" on the map, and search with no results), with an admin Feedback inbox that can apply suggested positions in one click. Data sources are visible to editors and admins only.

- **Public:**
    - home;
    - 13 interactive maps (Leaflet, schematic grid until base art exists);
    - layer and sub-type filters;
    - labels;
    - marker detail panel (confidence, source, version, nearby threats, report);
    - deep links;
    - global search (`/`, `⌘K`);
    - item database and item pages ("Should I keep this?");
    - objectives;
    - extraction pages;
    - raid planner;
    - about/data policy;
    - sitemap and robots.
- **Players:** notes, favourites and discovered markers, item tracker with recipe goals, routes (draw, reorder, label, save, share), confirm accuracy.
- **Admin and editor:**
    - dashboard (counts, attention items, analytics);
    - map editor (add by click, drag to move, place unplaced markers, draw shapes, link items and objectives, duplicate, verify, delete);
    - CRUD for maps (with base-image upload), items, objectives, recipes, sources, versions and marker taxonomy;
    - report moderation;
    - user roles;
    - JSON import (dry run) and export.
- **Data quality:** source reliability, confirmations, admin verification, open reports, version staleness, overrides, and automatic recalculation when the current build or a source's reliability changes.

## Current maps

These maps are seeded and published, each with a summary, region, variants and sourced field notes:

- Shegolskoe
- Ozernoe
- Factory
- Dogorsk
- Cargo Port
- Dam
- Park
- Military Base
- Scrapyard
- Headquarters
- Airport
- Downtown
- Gigastructure

**None has base artwork yet** (see [map-data-strategy.md](map-data-strategy.md)).

## Data coverage

| Data                    | Count | Placed / linked                        | Main source                                     |
| ----------------------- | ----- | -------------------------------------- | ----------------------------------------------- |
| Maps                    | 13    | —                                      | Official news                                   |
| Map locations (markers) | 85    | **0 positioned**                       | wiki.gg (58), official news (26), Fandom EN (1) |
| Items                   | 607   | 1 has a found-at link (Fracture Shard) | wiki.gg (CC BY-SA 4.0) and official listings    |
| Objectives              | 3     | 1 linked to a marker                   | wiki.gg                                         |
| Recipes and upgrades    | 0     | —                                      | No verifiable source found                      |
| Game versions           | 5     | current = 0.4.0.156                    | Official news                                   |

## Known inaccuracies and risks

- The regions of **Headquarters**, **Military Base** and **Scrapyard** come from the community wiki only.
- Wiki.gg has known copy-paste errors, so items sourced only from it carry "Low" confidence until verified.
- The "found at" relationships are almost empty, so the planner and "Found at" sections mostly report "not mapped yet". This is by design.
- The item `value` and `weight` columns are empty (not imported yet).

## Known limitations

- There is no base artwork, so every map shows the schematic grid.
- Multi-floor buildings are not handled (the `floor` column exists, but the UI has no floor switcher).
- There is no server-side rendering. Pages have correct meta tags, but crawlers must execute JS for the body text (SSR is scaffolded).
- Search uses `ILIKE`. That is fine at the current scale; add `pg_trgm` if the data grows a lot.
- The shared JS route-helper chunk (Wayfinder) is about 78 KB gzipped. Splitting it is a performance TODO.
- Map notes have share tokens but no public read-only note view yet.

## Test status

| Gate                                                        | Result                                                |
| ----------------------------------------------------------- | ----------------------------------------------------- |
| `php artisan test` (Pest; SQLite locally, PostgreSQL in CI) | **125 passed**, also verified on PostgreSQL           |
| `vendor/bin/phpstan analyse` (Larastan level 7)             | **0 errors**                                          |
| `vendor/bin/pint --test`                                    | pass                                                  |
| `npx vue-tsc --noEmit`                                      | pass                                                  |
| `npx vp check` (lint and format)                            | pass                                                  |
| `npm run build`                                             | pass                                                  |
| `tests/e2e/smoke.mjs` (Playwright, headless)                | **all steps pass, no JS errors** (desktop and mobile) |
| `migrate` + `db:seed --force` on fresh PostgreSQL           | pass: about 7 s, about 1,000 queries, idempotent      |

Test coverage includes:

- authorisation for guests, players, editors and admins;
- draft visibility;
- search ranking and no leaks;
- report deduplication, honeypot and HMAC fingerprint;
- confidence maths;
- editor create, move, duplicate and delete, plus cross-map protection;
- the import/export round trip, dry runs and atomic rollback;
- notes and routes privacy;
- the keep advisor;
- the planner ordering and extract choice;
- sitemap and robots.

## Deployment status

Ready for Laravel Cloud: follow [deployment.md](deployment.md). Deploy with `php artisan migrate --force`, then run `php artisan db:seed --force` once to load the data, then `php artisan app:make-admin <email>`. The CI workflow runs against a PostgreSQL service. Blockers before a public launch are decisions 1 and 2 above.

## Remaining TODOs

See [TODO.md](TODO.md). The critical path:

1. Base art for one map (for example Shegolskoe).
2. Position its 16 known places.
3. Verify the extract.
4. Record loot at locations.
5. Publish and ask the community for confirmations.

## Recommended next features

1. **Floor switcher**, for multi-level buildings such as the Gigastructure and the dam.
2. **Suggested edits**: a contributor role whose changes land in a review queue. This scales data entry without handing out editor rights.
3. **Patch watcher**: a scheduled job that polls the sequential official news IDs and creates a draft game version plus an admin notification.
4. **SSR**, for SEO on item pages.
5. **Wiki infobox import** (Price, Weight, Ref) with attribution, to fill item values.
