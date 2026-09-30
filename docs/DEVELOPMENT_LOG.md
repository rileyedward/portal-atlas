# Development log

## 2026-09-29: Milestones 0–8, first build

### Built

- **M0 Research:** a research agent surveyed official news (all 345 IDs), the FAQ, Steam, three wikis, competitors, GitHub and Gaijin's legal pages. Findings are in `research-notes.md`, summarised in `research.md` and `map-data-strategy.md`.
- **M1 Foundation:** the Laravel 13 Vue starter kit (Fortify auth and passkeys), switched to PostgreSQL.
    - The domain schema covers versions, sources, maps, the marker taxonomy, markers, items, recipes, objectives, reports, verifications, notes, tracked items, marker state, routes and analytics.
    - Enums, models, factories and policies.
- **M2 and M3 Map and intelligence:**
    - Leaflet `CRS.Simple` map with percentage coordinates and a schematic grid fallback.
    - Layer filters, labels, detail panel, global search, deep links, extraction pages.
- **M4 Content management:**
    - admin dashboard with analytics;
    - map editor;
    - CRUD for items, objectives, recipes, sources, versions and taxonomy;
    - report moderation;
    - role management;
    - JSON import and export (two formats).
- **M5 and M6 Player features:** notes, favourites and discovered markers, item tracker with goals, "keep" advisor, routes (draw, reorder, share), deterministic planner.
- **M7 Community:** reporting (honeypot, deduplication, rate limits, IP HMAC), confirmations, confidence model, version-aware staleness.
- **M8 Polish:** dark theme, mobile bottom sheets, SEO (meta tags, sitemap, robots), aggregate analytics.
- **Seed data:** 13 maps, 607 items, 3 objectives and 85 unplaced markers. Every record cites a source.

### Important decisions

- **Unplaced markers.** No source publishes coordinates, so `x`/`y` are nullable and sourced places are listed without positions rather than invented.
- **Brand "Portal Atlas".** Gaijin Content Creator Guidelines 1.1.7 forbid using the game title for a product name or domain.
- **Option C imagery** (original traced art). No official assets are licensed; datamining and extraction are prohibited.
- **Leaflet** over MapLibre/OpenLayers because the map is a flat image.
- **Deterministic planner and keep advisor**, with no LLM.
- **Tests run on PostgreSQL**, not SQLite, because search uses ILIKE and the schema uses jsonb.

### Problems and solutions

- **No local Postgres server** (Herd ships only the client). Used the `@embedded-postgres` npm binaries on port 5433; see development.md.
- **Larastan did not type pivot attributes.** Added `App\Support\Pivot` for typed access.
- **JsonResources in Inertia props were wrapped in `data`.** Call `->resolve()` in the controllers.
- **A stale route cache hid new routes.** Run `php artisan route:clear`.
- **PHP 8.4 has no pipe operator.** Replaced it with plain code.

### Remaining work

See [TODO.md](TODO.md). The critical path is **human in-game verification**: tracing base maps and positioning markers.

## 2026-09-29: activematterhelp.ru import

### Built

- Imported 5,952 positioned markers and area polygons across 12 maps, 1,370 loot pools (12k drop entries with chances) and about 1,000 new items (rarity, credit value, chronotraces) from activematterhelp.ru. The maintainer decided on this after being told about the licence and datamining risk.
- **Schema changes:**
    - a `loot_tables` table and an `item_loot_table` pivot (with chance);
    - `markers.variant`, `markers.loot_table_id` and `markers.external_ref` (unique per map);
    - `items.external_ref`.
- **Importers:** they now accept the new fields and match on `external_ref`. Partial rows no longer null out relations, and map metadata is merged instead of replaced.
- **`ItemLocator`:** "found at" now includes loot-table drops, aggregated per map and marker name, so a drop that's in 300 monster spawns shows as one line with a count. Search, the item page, the item index and the planner all use it.
- **UI:**
    - a raid-variant picker on maps (with `?variant=`);
    - a loot pool with drop chances in the marker panel;
    - a "Loot pools" section on item pages;
    - variant and loot pool fields in the editor;
    - a variant choice in the planner;
    - nearby threats grouped;
    - the unplaced list collapsed once a map has positions.
- **New marker types and icons** for the site's categories.

### Decisions

- Their map images are not copied. Maps stay on the schematic grid until original base art exists.
- Imported data keeps its own source (reliability 70), so nothing looks verified until someone confirms it in game.
- The data policy text on the About page, the README and the map banner was corrected to disclose the third-party import.

## 2026-09-29: Player feedback and hidden sources

### Built

- **Feedback system.** The existing `reports` table now holds all player feedback:
    - the subject is optional (general feedback);
    - new types: "Something's broken", "Missing information" and "Suggestion or idea";
    - optional reply email for guests, the page it was sent from, a short context label ("Loot pools", "Search: radio"), and a suggested map position.
- **Anti-abuse rules still apply:** honeypot, rate limits, deduplication per reporter, and the IP HMAC fingerprint. Only the page path is stored, never another host.
- **One global `FeedbackDialog`** (`useFeedback().openFeedback()`), mounted in the public layout and on the map page. Entry points:
    - a floating Feedback button and a footer link;
    - a map header button;
    - flag icons on the marker panel sections, item and objective sections, extraction rows and the map's field notes;
    - "Tell us what's missing" when search finds nothing;
    - "Wrong spot? / Suggest position" on markers, which enters a map-click mode and sends the clicked position.
- **Admin Feedback inbox** (`/admin/reports`):
    - status tabs, kind filter (data, general, with position), type filter and search;
    - full context: reporter email for replies, source page, current and suggested position;
    - "Fix it" links to the right editor, and one-click **Apply position**, which moves the marker and resolves the feedback;
    - resolve, dismiss and reopen with notes;
    - an open-count badge in the admin menu.
- **Sources are hidden from players.**
    - `App\Support\SourceVisibility` strips source names, links, notes, map-image attribution, map calibration and raw import metadata from every public payload.
    - Descriptions and field notes no longer mention where data came from.
    - Editors and admins still see everything.
    - The About page no longer names sources.

## 2026-09-30: Production readiness

- **Data loading moved from a migration to `php artisan db:seed --force`.** On the production server, the per-row import took about 10 minutes and the deploy was cancelled. The importers are now batched: 82,587 queries became 981, and a full seed takes about 7 seconds on Postgres. Re-running it is idempotent (every row reports "unchanged"), and stored confidence scores are identical to a full recalculation.
- The bundled map images moved into `public/map-images`, because Cloud's filesystem is ephemeral. Admin uploads use a configurable `MEDIA_DISK`.
- **Hardening:** trusted proxies, security headers, branded error pages (the new `Error` page), and the `app:make-admin` command for the first admin.
- [deployment.md](deployment.md) was rewritten for Laravel Cloud, with the full list of environment variables.
