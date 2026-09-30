# Deployment (Laravel Cloud)

The app is a standard Laravel 13 + Inertia app with PostgreSQL. These steps target [Laravel Cloud](https://cloud.laravel.com), and the same variables apply on any Laravel host.

## 1. Create the environment

1. Create the application from the Git repository. PHP **8.4**; Node 20+ is used for the build.
2. **Attach a PostgreSQL database.** Cloud injects the `DB_*` connection variables.
3. _(Optional, recommended)_ **Attach an object-storage bucket** for map images that admins upload. See `MEDIA_DISK` below. The 12 bundled map images ship in `public/map-images` and do not need a bucket.
4. Build commands (Cloud's defaults are fine):
    ```bash
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    ```
5. Deploy command:
    ```bash
    php artisan migrate --force
    ```
    Migrations only create tables. They load no data, so they finish in seconds.

## 2. Environment variables

### You must set these

Cloud does not set these for you, or sets defaults that are wrong for this app.

| Variable                         | Example                                                                                                       | Why                                                                                                                                                |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_NAME`                       | `"Portal Atlas"`                                                                                              | Page titles, emails and branding. The default is "Laravel". Do not use the game's name (see [map-data-strategy.md](map-data-strategy.md))          |
| `VITE_APP_NAME`                  | `"Portal Atlas"`                                                                                              | Browser tab titles. It is read at **build time**, so set it in the environment before deploying                                                    |
| `APP_URL`                        | `https://yourdomain.com`                                                                                      | Absolute URLs in the sitemap, emails and password-reset links, and the **passkey relying-party domain**. Update it when you attach a custom domain |
| `MAIL_MAILER`                    | `resend`, `postmark`, `smtp`, `ses`                                                                           | Password resets, email verification (the admin panel requires a verified email) and account emails. The default `log` mailer sends nothing         |
| Mail credentials for that mailer | `RESEND_API_KEY=…`, or `POSTMARK_API_KEY=…`, or `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | Needed to actually deliver mail                                                                                                                    |
| `MAIL_FROM_ADDRESS`              | `noreply@yourdomain.com`                                                                                      | Must be a sender your mail provider has verified                                                                                                   |
| `MAIL_FROM_NAME`                 | `"Portal Atlas"`                                                                                              | Sender name                                                                                                                                        |

### Set these if they apply

| Variable                      | Value                                                            | When                                                                                                                      |
| ----------------------------- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| `MEDIA_DISK`                  | the disk name of your Cloud bucket (for example `s3` or `media`) | If admins will upload map images. Without a persistent disk, uploads are lost on the next deploy. The default is `public` |
| `SESSION_SECURE_COOKIE`       | `true`                                                           | Recommended in production (HTTPS-only session cookie)                                                                     |
| `LOG_LEVEL`                   | `warning`                                                        | Quieter production logs                                                                                                   |
| `PASSKEYS_USER_HANDLE_SECRET` | a long random string                                             | Optional. It defaults to `APP_KEY`; set it separately if you might rotate `APP_KEY`                                       |

### Provided by Laravel Cloud

Check that these are present on the environment's Variables page:

- `APP_KEY`, `APP_ENV=production` and `APP_DEBUG=false`.
- `DB_CONNECTION` and the other `DB_*` connection details, injected when you attach the Postgres database. They must be `pgsql`.
- The bucket's `AWS_*` / filesystem variables, when a bucket is attached.
- `CACHE_STORE` and `REDIS_*`, if you attach a key-value store. Otherwise the database cache is used.
- `LOG_CHANNEL`, which Cloud's log viewer uses.

### Defaults that are fine as they are

- `SESSION_DRIVER=database`, `CACHE_STORE=database` and `QUEUE_CONNECTION=database`. The app queues nothing, so **no queue worker is required**.
- **Enable the scheduler** in Cloud. It runs a daily `model:prune` that deletes page-view analytics older than 90 days. If the scheduler is off, the site still works; old rows are just never removed.

## 3. First deploy: load the data

After the first successful deploy, open **Commands** in Cloud (or any shell on the server) and run:

```bash
php artisan db:seed --force
```

This loads the full dataset: marker types, game versions, 13 maps (12 with base images), about 1,600 items, 1,370 loot pools, objectives and about 6,000 markers. It takes about 10 seconds, because the import batches its writes (around 1,000 queries in total). It is **safe to run again**: every record is upserted by a stable key, and unchanged rows are skipped. Run it again whenever `database/data` changes.

`db:seed` never creates users in production. (The `admin@test.com` test admin is only created when `APP_ENV=local`.)

## 4. Create your admin account

1. Register on the live site at `/register`.
2. Run:
    ```bash
    php artisan app:make-admin you@yourdomain.com
    ```
    This makes the account an admin and marks its email as verified. Use `--role=editor` to give someone content access without user management.
3. Visit `/admin`.

## 5. Custom domain

Attach the domain in Cloud and update `APP_URL`, then redeploy so the build picks it up. **Don't use a domain containing the game's name.**

## What the app already handles for production

- **Trusted proxies** (`bootstrap/app.php`). Cloud's load balancer is trusted, so rate limits and logs see real player IPs, and URLs are generated as https.
- **Security headers** on every response: `nosniff`, `SAMEORIGIN` framing, a referrer policy, a permissions policy, and HSTS over HTTPS.
- **Branded error pages** for 403, 404, 429, 500 and 503 in production. The API keeps returning JSON errors, and an expired form (419) returns to the page with a message.
- **Rate limits:** search 120 per minute, feedback 3 per minute and 30 per day, player writes 60 per minute.
- **Health check** at `/up`.
- **`php artisan optimize`** works, including route caching.

## Production checklist

- [ ] PostgreSQL attached; `DB_CONNECTION=pgsql`
- [ ] `APP_NAME`, `VITE_APP_NAME` and `APP_URL` set (the real domain)
- [ ] Mail configured and a test password-reset email received
- [ ] `MEDIA_DISK` points at a bucket (only if admins will upload map images)
- [ ] Deployed; `php artisan migrate --force` succeeded
- [ ] `php artisan db:seed --force` run once
- [ ] Registered and ran `php artisan app:make-admin <email>`
- [ ] Health check pointed at `/up`
- [ ] Automated database backups enabled, with a restore tested
- [ ] `sitemap.xml` submitted to search engines
- [ ] Domain does not contain the game's name
- [ ] Legal questions reviewed (see [PROJECT_STATUS.md](PROJECT_STATUS.md))

## Backups and restore

```bash
pg_dump --format=custom "$DATABASE_URL" > backup.dump
pg_restore --clean --no-owner -d "$DATABASE_URL" backup.dump
```

Map datasets can also be exported per map as JSON (Admin → Import / export) for a human-readable backup.
