# Development

## Requirements

- Laravel Herd (PHP 8.4 with `pdo_sqlite`; add `pdo_pgsql` to test against Postgres)
- Composer 2
- Node 20+ and npm
- PostgreSQL 16+ only if you want to mirror production locally

## Setup (local dev uses SQLite + Laravel Herd)

```bash
cp .env.example .env            # DB_CONNECTION=sqlite, APP_URL=http://active-matter-map.test
composer install
npm install
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed # sourced dataset + local admin
php artisan storage:link         # for uploaded map base images
herd link active-matter-map      # once; serves http://active-matter-map.test
npm run build                    # or `npm run dev` for hot reload
```

Open http://active-matter-map.test and log in as **admin@test.com / password** (created only when `APP_ENV=local`) to reach `/admin`.

Local dev and the test suite use SQLite (tests run in memory). **Production and CI use PostgreSQL**: the code sticks to portable queries (`whereLike` compiles to `ILIKE` on Postgres and `LIKE` on SQLite), and the CI workflow runs the suite against a Postgres service. To develop against Postgres locally, switch the `DB_*` block in `.env` (see the commented values).

## Tests and quality gates

The test suite runs on in-memory SQLite (see `phpunit.xml`). CI overrides the `DB_*` variables to run the same suite on PostgreSQL.

```bash
php artisan test                  # Pest feature + unit tests
vendor/bin/pint                   # PHP formatting
vendor/bin/phpstan analyse        # Larastan level 7
npx vue-tsc --noEmit              # TypeScript
npx vp check                      # frontend lint + format check (Vite+)
npm run build                     # production build
composer run ci:check             # most of the above in one go
```

## Useful commands

```bash
php artisan wayfinder:generate --with-form     # regenerate typed route helpers (Vite does this automatically)
php artisan game-data:import file.json --dry-run
python3 database/data/tools/build_items.py     # rebuild items.json from research notes
python3 database/data/tools/build_reference.py # rebuild maps/objectives/markers seed files
```

## Conventions

- **Controllers** stay thin. Multi-step writes go in `app/Actions`; reusable domain logic goes in `app/Services`.
- **Every controlled vocabulary** is a backed enum in `app/Enums` with `label()` and `options()`.
- **Public data models** use `HasDataQuality`. After any write that affects reports or verifications, call `ConfidenceCalculator::refresh()`.
- **Frontend JSON calls** use `resources/js/lib/http.ts`, which sends the XSRF token. Inertia page navigation uses Wayfinder helpers from `@/routes/...`.
- **Marker icons** are Lucide keys registered in `resources/js/lib/markerIcons.ts`. To use a new key, add it there.
- **Never add fake game data**, including in factories used by the seeders. Factories exist only for tests.
