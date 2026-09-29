# Data model

```text
game_versions ─┐
sources ───────┼──< maps ──< markers >──┬── marker_types >── marker_categories
               │               │        ├──< item_marker >── items >── item_categories
               │               │        └──< marker_objective >── objectives
               │               └──< objectives ──< item_objective >── items
               │
items ──< recipe_ingredients >── recipes (kind: crafting | upgrade; optional output_item)

reports (morph: marker|item|objective)      verifications (morph, per user per version)
users ──< map_notes   users ──< raid_routes   users ──< marker_user (favorite/discovered)
users ──< item_user (intent, need/own)      analytics_events (anonymous aggregates)
```

## Tables

### `game_versions`

`version` (unique, e.g. `0.4.0.156`), `name`, `released_at`, `is_current` (exactly one), `notes`, `source_url`.

### `sources`

Where a fact came from: `name`, `kind` (`SourceKind`: official, official_media, community_wiki, community_tool, player_report, admin_observation, datamine), `url`, `reliability` (0–100, the base input to confidence).

### `maps`

`slug`, `name`, `summary`, `description`, `status` (draft/published/archived), `image_path` and `image_attribution` (optional base layer), `width` and `height` (aspect of the coordinate space), `sort_order`, `game_version_id`, `source_id`, `source_url`, `metadata` (jsonb: `region`, `region_note`, `variants[]`, `facts[{text, source_url, confidence}]`).

### `marker_categories`, `marker_types`

The configurable taxonomy. There are five categories (Locations, Extracts, Loot, Objectives, Threats) and their types (for example extraction-point, conditional-extraction, dynamic-extraction, loot-location, container, enemy, anomaly, hazard, pvp-hotspot). Each type has an `icon` (a Lucide key, see `resources/js/lib/markerIcons.ts`), an optional `color`, and a `geometry` (point, polygon or polyline).

### `markers`

| Column                                                             | Notes                                                                                            |
| ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------ |
| `x`, `y`                                                           | decimal(7,4), percent of width/height from the top left. **NULL = known but not yet positioned** |
| `geometry`                                                         | jsonb list of `[x, y]` for areas and paths                                                       |
| `floor`                                                            | optional level label                                                                             |
| `status`, `is_visible`                                             | only `published` + visible markers on published maps are public                                  |
| `metadata`                                                         | jsonb, e.g. `conditions` for extracts                                                            |
| `source_id`, `source_url`, `source_note`                           | provenance                                                                                       |
| `confidence`                                                       | cached computed score. `confidence_override` is an admin override                                |
| `confirmations_count`, `open_reports_count`                        | cached counters                                                                                  |
| `last_verified_at`, `introduced_version_id`, `verified_version_id` | versioning                                                                                       |
| `created_by`, `updated_by`, soft deletes                           | audit trail                                                                                      |

### `items`, `item_categories`

Items have `slug`, `name`, `description`, `category`, `rarity` (free text constrained to the game's tiers by convention: Common, Uncommon, Rare, Epic), `value`, `weight`, `icon_path` (only for legally usable art; none yet), `metadata.tags`, and the same data-quality columns as markers.

### The item usage graph

- **Found at:** `item_marker` (`likelihood`: guaranteed, common, uncommon or rare; `note`).
- **Used for crafting or upgrades:** `recipe_ingredients` joins to `recipes` (`kind`, `station`, `level`, `output_item_id`).
- **Used for objectives:** `item_objective` (`role`: required or reward; `quantity`).
- **Player goals:** `item_user` (`intent`, `quantity_needed`, `quantity_owned`).

`KeepAdvisor` combines these to answer "Should I keep this?". `RaidPlanner` uses _found at_ plus marker positions to answer "Where do I go?".

### `objectives`

`kind` (objective, quest, investigation, contract, mission), optional `map_id`, `giver`, `rewards` (text), data-quality columns. Linked to markers (`marker_objective.role`) and items.

### Community tables

- **`reports`:** a polymorphic `reportable` (marker, item or objective, through the morph map), `type` (`ReportType`), `message` (HTML stripped), `status` (open, accepted or rejected), `reporter_fingerprint` (an HMAC of the IP with the app key, never the raw IP), and resolution fields.
- **`verifications`:** unique per (subject, user, game version). `is_admin` marks verification by an editor or admin.

### Player tables

- **`map_notes`:** private by default; `is_shared` plus a `share_token`.
- **`marker_user`:** `is_favorite`, `discovered_at`.
- **`raid_routes`:** `points` is jsonb `[{x, y, marker_id?, label?}]`, with 2–50 points; `is_public` plus a `share_token`.

### `analytics_events`

`name` (one of map_view, search, marker_open, filter_toggle, item_view), `subject_type` and `subject_id`, `term` (lower-cased search text), `result_count`, and `created_at`. **No user, IP or device data.**

## Confidence model (`App\Services\ConfidenceCalculator`)

```text
score = source.reliability (20 if unsourced)
      + min(8 × community confirmations, 30)
      + 15 if an editor/admin verified it
      − min(12 × open reports, 48)
      − 15 if never verified   OR   − 15 if verified for an older build than the current one
      − 10 if last verified more than 180 days ago
clamped to 0..100; confidence_override (admin) wins when set.
```

Players see a bar and a label (High ≥ 80, Medium ≥ 55, Low ≥ 30, otherwise Unverified). Admins see the stored score and can override it. The factor breakdown is available through `ConfidenceCalculator::breakdown()`.
