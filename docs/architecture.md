# Architecture

## Stack

- **Backend:** Laravel 13, PHP 8.4, PostgreSQL 16+ (developed on 18), Fortify (auth and passkeys), Inertia v3.
- **Frontend:** Vue 3, TypeScript, Tailwind CSS v4, shadcn-vue (reka-ui), Wayfinder (typed route helpers), Vite+ (`vp`) for build, lint and format.
- **Map:** Leaflet 1.9 with `CRS.Simple`, and Lucide icon SVGs rendered into `divIcon`s.

### Why Leaflet

We evaluated Leaflet, MapLibre and OpenLayers. The map is a flat game image, not a geographic map. Leaflet's `CRS.Simple`, image overlays, divIcon markers, polygons and polylines cover everything the app needs, in a small bundle with a simple API. MapLibre is aimed at vector tiles and geographic projections; OpenLayers is powerful but heavy. If marker counts ever exceed a few thousand per map, add clustering or a canvas marker layer (see [TODO.md](TODO.md)).

## Layout

```text
app/
  Actions/Community/      SubmitReport, ConfirmAccuracy, ResolveReport (single-purpose writes)
  Enums/                  Backed enums for every controlled vocabulary (status, kinds, roles...)
  Http/Controllers/       Public pages; Api/ (JSON for the map); Player/ (account features); Admin/
  Http/Requests/          Form requests (admin marker, player notes/routes, reports)
  Http/Resources/         Map/marker payloads (compact list vs full detail)
  Models/                 Eloquent models; Concerns/HasDataQuality shared by Marker/Item/Objective
  Policies/               Map/Marker visibility, note/route ownership
  Services/               ConfidenceCalculator, SearchService, RaidPlanner, KeepAdvisor, Analytics
  Services/DataExchange/  JSON import/export (map datasets + reference game data)
  Support/                ConfidenceBreakdown value object, typed pivot access
database/data/            Sourced seed datasets (JSON) + tools/ that build them from docs/research-notes.md
resources/js/
  pages/                  Inertia pages (maps/Show is the product; admin/maps/Editor is the editor)
  components/map/         GameMap (Leaflet), LayerPanel, MarkerDetailPanel, RoutePanel, NoteDialog
  components/game/        Brand, header/footer, GlobalSearch, ConfidenceMeter, ReportDialog
  layouts/                PublicLayout, AdminLayout (+ starter auth/settings layouts)
  lib/http.ts             Fetch wrapper with XSRF for JSON endpoints
```

## Key decisions

| Decision                                                                   | Rationale                                                                                          |
| -------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Normalised percentage coordinates                                          | Survive base-image replacement; make import files readable (`"x": 42.18`)                          |
| Nullable coordinates ("unplaced")                                          | Sources confirm places without positions. Storing them keeps the facts without inventing locations |
| Marker taxonomy in the database (`marker_categories`, `marker_types`)      | Admins can add types without a deploy; the frontend never hard-codes categories                    |
| One `recipes` table for crafting _and_ upgrades                            | Both are "a target that consumes items". Simplifies the item usage graph                           |
| Data quality columns on every public record (`HasDataQuality`)             | Source, confidence, version introduced and verified, last verified, report and confirmation counts |
| Cached `confidence` column, recomputed by `ConfidenceCalculator` on writes | Cheap reads for thousands of markers; the full breakdown stays available for admins                |
| JSON endpoints under `/api/*` on the **web** middleware stack              | Session auth and CSRF for writes; no tokens needed for a first-party SPA                           |
| Wayfinder route helpers                                                    | Typed, refactor-safe URLs in Vue                                                                   |
| Deterministic planner (no LLM)                                             | Only uses known relationships; explainable output                                                  |
| Aggregate-only analytics table                                             | No user IDs, IPs or user agents                                                                    |
| Brand is `APP_NAME` ("Portal Atlas")                                       | Gaijin guidelines forbid using the game title as a product name or domain                          |

## Request flow: opening a map

1. `GET /maps/{slug}` goes to `MapController@show`. It authorises through `MapPolicy` (published, or the user is an editor).
2. The controller sends compact markers (`MarkerResource`: id, type, name, x, y, geometry, confidence), the taxonomy, and the player's personal data if signed in.
3. `maps/Show.vue` renders `GameMap`. Layer filters persist in `sessionStorage`.
4. Clicking a marker loads `GET /api/markers/{id}` (`MarkerDetailResource`) into the detail panel.
5. Search sends `GET /api/search?q=` to `SearchService`, which runs ILIKE queries: every word must match, and exact and prefix matches rank first.

## Authorisation model

| Role   | Can                                                                                                                                                 |
| ------ | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Guest  | Browse published maps, items and objectives; search; plan raids; report (rate limited, honeypot, deduplicated)                                      |
| Player | Everything a guest can, plus notes, favourites, discovered markers, tracked items, routes, and confirming accuracy                                  |
| Editor | Everything a player can, plus the admin panel (content, map editor, reports, import/export); sees drafts; their confirmations count as verification |
| Admin  | Everything an editor can, plus user role management                                                                                                 |

`role` is not mass-assignable. It is changed only in Admin → Users, and an admin cannot change their own role.
