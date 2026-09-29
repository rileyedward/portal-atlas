# Portal Atlas

An unofficial, community-made interactive map and raid companion for Active Matter.

Portal Atlas helps a player answer:

- **Where am I?**
- **Where do I extract?**
- **Where can I find this item?**
- **What is this place?**
- **What should I keep?**

Every data point carries its **source**, a **confidence score**, the **game version** it was verified for, and a way to **report** it when it is wrong. Nothing is guessed: unknown values are shown as unknown.

> Not produced, approved or endorsed by Gaijin Entertainment or Matter Team. See [docs/map-data-strategy.md](docs/map-data-strategy.md) for why the product is not named after the game.

## Features

- **Interactive maps** (Leaflet, flat CRS):
    - pan, zoom and reset;
    - layer and sub-type filters that persist for the session;
    - labels;
    - a marker detail panel with confidence, source, version and nearby threats;
    - deep links (`/maps/factory?marker=12`);
    - a mobile bottom sheet.
- **Unplaced intel.** Places confirmed by sources but not yet positioned are listed and searchable instead of being faked onto the map.
- **Global search** (`/` or `⌘K`) across items, markers, objectives and maps. Item results show where the item is found, and choosing a result jumps to the map and highlights the marker.
- **Item database** with a usage graph (found at, crafting and upgrades, objectives) and a **"Should I keep this?"** advisor.
- **Player accounts:**
    - private map notes;
    - favourites and discovered markers;
    - an item tracker (need, have, keep, sell, etc.) with recipe goals;
    - raid routes with drawing, reordering and share links;
    - a deterministic **raid planner**.
- **Community data quality:**
    - reports (anonymous allowed, rate-limited, with a honeypot);
    - confirmations;
    - the confidence model;
    - game-version tracking.
- **Admin:**
    - dashboard with analytics;
    - map editor (click to add, drag to move, place unplaced markers, draw shapes, link loot and objectives, duplicate, verify);
    - CRUD for items, objectives, recipes, sources, versions and marker types;
    - report moderation;
    - user roles;
    - JSON import and export.
- **SEO:** readable URLs (`/maps/factory`, `/items/{slug}`, `/objectives/{slug}`, `/extractions/{map}`), meta tags, `sitemap.xml` and `robots.txt`.

## Quick start

```bash
cp .env.example .env        # SQLite + Herd (http://active-matter-map.test)
composer install && npm install
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed   # sourced dataset + local admin (admin@test.com / password)
php artisan storage:link
herd link active-matter-map && npm run build
```

The full guide, including running PostgreSQL without admin rights, is in [docs/development.md](docs/development.md).

## Quality gates

```bash
php artisan test            # Pest (SQLite locally, PostgreSQL in CI)
vendor/bin/phpstan analyse  # Larastan level 7
vendor/bin/pint --test
npx vue-tsc --noEmit
npx vp check
npm run build
```

## Documentation

| Doc                                                                     | What                                               |
| ----------------------------------------------------------------------- | -------------------------------------------------- |
| [PROJECT_STATUS.md](docs/PROJECT_STATUS.md)                             | Current state, coverage, limitations, next steps   |
| [research.md](docs/research.md)                                         | Game research, sources, competitors, legal         |
| [map-data-strategy.md](docs/map-data-strategy.md)                       | How map imagery is sourced (and why not otherwise) |
| [architecture.md](docs/architecture.md)                                 | Stack, structure, decisions                        |
| [data-model.md](docs/data-model.md)                                     | Tables, item usage graph, confidence model         |
| [data-import.md](docs/data-import.md)                                   | JSON formats, seed datasets, contribution workflow |
| [content-guidelines.md](docs/content-guidelines.md)                     | Rules for editors                                  |
| [development.md](docs/development.md)                                   | Local setup and conventions                        |
| [deployment.md](docs/deployment.md)                                     | Production setup and checklist                     |
| [TODO.md](docs/TODO.md) / [DEVELOPMENT_LOG.md](docs/DEVELOPMENT_LOG.md) | Roadmap and history                                |

## Data licensing

Item names, categories and rarities partly come from [activematter.wiki.gg](https://activematter.wiki.gg) under **CC BY-SA 4.0**. Each record links to its source page. Facts from official news link to [activematter.game](https://activematter.game/en/news). Most map positions, loot pools and drop chances are imported from the fan site [activematterhelp.ru](https://activematterhelp.ru/maps) (© Active Matter Help, no licence granted; imported at the project maintainer's request, see [data-import.md](docs/data-import.md)). No extracted game images are hosted.
