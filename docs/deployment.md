# Deployment

The target is Laravel Cloud (recommended) or any Laravel host with PHP 8.4 and PostgreSQL.

## Environment

```dotenv
APP_NAME="Portal Atlas"          # do NOT use the game's title (Gaijin guidelines 1.1.7)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example   # no game name in the domain
DB_CONNECTION=pgsql
DB_URL=...                       # or DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database             # or redis
QUEUE_CONNECTION=database        # nothing heavy is queued today; mail (verification) benefits
FILESYSTEM_DISK=public           # map base images; use s3/r2 in multi-instance setups
MAIL_MAILER=...                  # needed for email verification and password resets
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

If you store map images on S3 or R2, set `FILESYSTEM_DISK` and configure the `public` disk (or change `Map::imageUrl()` to use your disk).

## Build and release

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=MarkerTaxonomySeeder --force   # first deploy only (idempotent)
php artisan db:seed --class=GameDataSeeder --force         # first deploy / when dataset files change (idempotent upserts)
php artisan storage:link
php artisan optimize
```

**Do not run the full `DatabaseSeeder` expecting an admin account in production.** The admin account is only created locally. Create your first admin with:

```bash
php artisan tinker --execute="App\Models\User::where('email','you@example.com')->first()->forceFill(['role'=>'admin'])->save();"
```

## Production checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, app key set
- [ ] HTTPS enforced (Laravel Cloud does this by default); `SESSION_SECURE_COOKIE=true`
- [ ] PostgreSQL provisioned; **automated daily backups** with at least 7 days of retention; restore tested
- [ ] `php artisan migrate --force` in the deploy script
- [ ] Seeders run once (taxonomy and game data)
- [ ] First admin promoted with the tinker command above
- [ ] Mail configured (verification and password reset)
- [ ] `storage:link` or cloud disk configured for map images
- [ ] Health check pointed at `/up`
- [ ] Error tracking (Laravel Cloud logs, Nightwatch, Sentry or Flare) and log level `warning`
- [ ] Queue worker running if `QUEUE_CONNECTION` is not `sync`
- [ ] `php artisan optimize` after each deploy
- [ ] Rate limits reviewed (`AppServiceProvider::configureRateLimiting`: search 120/min, reports 3/min and 30/day, player writes 60/min)
- [ ] Domain has **no** game title in it; footer non-affiliation notice is visible
- [ ] Legal question about ToS 4.1 "database creation" resolved (see PROJECT_STATUS.md)
- [ ] `sitemap.xml` submitted to search engines; `robots.txt` allows `/` and disallows `/admin`

## Backups and restore

```bash
pg_dump --format=custom "$DATABASE_URL" > backup.dump
pg_restore --clean --no-owner -d "$DATABASE_URL" backup.dump
```

Map datasets can also be exported per map as JSON (Admin → Import / export). Commit those files to git for a human-readable second backup.
