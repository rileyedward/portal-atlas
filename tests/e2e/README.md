# End-to-end smoke test

A Playwright script that drives the real app: every public and admin page renders, the map and schematic grid render, unplaced markers open the detail panel, layer toggles, global search → item → jump to map, reporting, admin login, placing a marker in the editor, clicking it on the public map, drawing and saving a route, placing a note, and mobile rendering. It fails on any uncaught JS error.

**They write data (place a marker with a test position, create feedback, a route and a note). Run them only against a disposable database, then reseed.**

```bash
php artisan migrate:fresh --seed        # local admin: admin@test.com / password
npm run build
npm i --no-save playwright && npx playwright install chromium
BASE_URL=http://active-matter-map.test node tests/e2e/smoke.mjs
BASE_URL=http://active-matter-map.test node tests/e2e/feedback.mjs   # feedback form, suggested positions, admin inbox, hidden sources
php artisan migrate:fresh --seed        # discard test data
```
