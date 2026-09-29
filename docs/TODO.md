# TODO

Legend: **[blocked]** needs a human or external input; **[next]** highest value.

## Research

- [ ] **[blocked]** Ask Gaijin (contentpartners@gaijin.net) about ToS 4.1 "database creation" and fan map imagery; record the answer in research.md
- [ ] Confirm Headquarters region (Africa vs Dalniy Island) and Military Base/Scrapyard regions in game
- [ ] Review r/ActiveMatter and the official Discord (blocked during research: 403 or login needed)
- [ ] Watch official news IDs above 345 for patch notes; add versions promptly
- [ ] Find a verifiable source for crafting recipes, Replicator/Refiner inputs and Shelter upgrades

## Infrastructure

- [x] Laravel 13 + Inertia v3 + Vue 3 + TS + Tailwind 4, PostgreSQL, Pest, Larastan L7
- [x] CI workflow runs against a PostgreSQL service
- [x] Playwright smoke test (`tests/e2e/smoke.mjs`) covering map, search, filter, marker panel, editor, route, note, report
- [ ] Run the E2E smoke test in CI (needs a built app + seeded Postgres)
- [ ] Frontend component tests (Vitest) for LayerPanel, GlobalSearch keyboard navigation and the RoutePanel reorder logic

## Maps

- [ ] **[blocked][next]** Trace original base maps from own gameplay (see map-data-strategy.md), starting with Shegolskoe and Factory
- [ ] **[blocked][next]** Position the 85 seeded unplaced markers in the editor after in-game verification
- [ ] Multi-floor support in the UI (the `floor` column exists; add a floor switcher)
- [ ] Per-map calibration or rotation if traced images need it

## Loot

- [ ] Record found-at relationships (`item_marker`) from in-game observation
- [ ] Add item values and weights (the wiki infobox has Price, Weight and Ref fields; import them with attribution after spot checks)
- [ ] Item icons: only original or explicitly licensed art

## Objectives

- [ ] Import investigations and contracts from wiki.gg with attribution and spot checks
- [ ] Link objective steps to markers

## Enemies

- [ ] Threat zones as polygons once base maps exist
- [ ] Boss spawn areas (Alpha Flowerman, Alpha Mimic) once positions are verified

## Admin

- [ ] Bulk edit (status, source or version for many markers)
- [x] Recalculate confidence automatically on source-reliability and current-version changes (`php artisan confidence:recalculate` for manual runs)
- [ ] Activity/audit log view (`created_by` and `updated_by` exist)
- [ ] Undo for marker moves in the editor

## Users

- [ ] Share links for notes (`share_token` exists; add a read-only view)
- [ ] Import and export personal data
- [ ] "Filters as user preferences" (currently session-only)

## Routes

- [ ] Route distance/time estimates once real map scale is known
- [ ] Planner: optional start point and "avoid threats" toggle

## Performance

- [ ] Measure with realistic marker counts (5k+) and add clustering or a canvas marker layer if needed
- [ ] `pg_trgm` index for search if the dataset grows past tens of thousands of rows
- [ ] Cache the map payload per map and version

## SEO

- [ ] Server-side rendering (Inertia SSR is scaffolded: `npm run build:ssr`) so crawlers get full HTML
- [ ] Open Graph images per map

## Deployment

- [ ] Choose a domain **without** the game title
- [ ] Provision Laravel Cloud and PostgreSQL with backups; follow the deployment.md checklist

## Future

- [ ] Patch-diff view ("what changed in 0.4.0.x")
- [ ] Contributor role with a review queue (suggested edits instead of direct writes)
- [ ] Localisation (the game ships in 7 languages)
