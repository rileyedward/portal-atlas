import { chromium } from 'playwright';

const BASE = process.env.BASE_URL ?? 'http://active-matter-map.test';
const browser = await chromium.launch();
const results = [];
const errors = [];

async function page(ctx, label) {
    const p = await ctx.newPage();
    p.on('pageerror', (e) => errors.push(`[${label}] pageerror: ${e.message}`));
    p.on(
        'console',
        (m) =>
            m.type() === 'error' &&
            errors.push(`[${label}] console: ${m.text()}`),
    );
    return p;
}

async function step(name, fn) {
    try {
        await fn();
        results.push(`PASS ${name}`);
    } catch (e) {
        results.push(`FAIL ${name}: ${e.message.split('\n')[0]}`);
    }
}

const ctx = await browser.newContext({
    viewport: { width: 1400, height: 900 },
});
const p = await page(ctx, 'public');

for (const url of [
    '/',
    '/items',
    '/items/active-matter',
    '/objectives',
    '/objectives/active-matter-collection',
    '/extractions/factory',
    '/planner',
    '/about',
]) {
    await step(`render ${url}`, async () => {
        await p.goto(BASE + url, { waitUntil: 'networkidle' });
        await p.waitForSelector('main h1, h1', { timeout: 5000 });
    });
}

await step('map renders leaflet + schematic grid', async () => {
    await p.goto(BASE + '/maps/shegolskoe', { waitUntil: 'networkidle' });
    await p.waitForSelector('.leaflet-container');
    await p.waitForSelector('.leaflet-overlay-pane svg');
});
await p.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/map-shegolskoe.png`,
});

await step('unplaced marker opens detail panel', async () => {
    await p.getByText(/Known, not yet positioned/).click();
    await p
        .getByRole('button', { name: /Water tower/ })
        .first()
        .click();
    await p.getByText('Known from a source').waitFor({ timeout: 5000 });
    await p.getByText('Data quality').waitFor();
});
await p.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/map-detail.png`,
});

await step('layer toggle hides category', async () => {
    await p.locator('#layer-navigation').click();
});

await step('global search → item → jumps to map', async () => {
    await p.keyboard.press('Escape');
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    await p.keyboard.press('/');
    await p.keyboard.type('fracture');
    await p
        .getByRole('option', { name: /Fracture Shard/ })
        .waitFor({ timeout: 5000 });
    // Item results list where the item drops; follow the first place.
    await p
        .getByRole('option', { name: /Fracture Shard/ })
        .getByRole('button')
        .first()
        .click();
    await p.waitForURL(/maps\/[a-z-]+\?marker=/);
    await p.getByText('Data quality').waitFor();
});

await step('report dialog submits', async () => {
    await p.getByRole('button', { name: /Report a problem/ }).click();
    await p.getByLabel('Outdated information').check();
    await p.getByRole('button', { name: 'Send feedback' }).last().click();
    await p.getByText(/Your feedback was sent/).waitFor({ timeout: 5000 });
});

// Admin flow
const admin = await browser.newContext({
    viewport: { width: 1500, height: 950 },
});
const a = await page(admin, 'admin');
await step('admin login', async () => {
    await a.goto(BASE + '/login');
    await a.getByLabel('Email address').fill('admin@test.com');
    await a.getByLabel('Password', { exact: true }).fill('password');
    await a.getByRole('button', { name: /Log in/i }).click();
    await a.waitForURL((u) => !u.pathname.startsWith('/login'));
});
for (const url of [
    '/admin',
    '/admin/maps',
    '/admin/items',
    '/admin/objectives',
    '/admin/recipes',
    '/admin/reports',
    '/admin/versions',
    '/admin/sources',
    '/admin/taxonomy',
    '/admin/users',
    '/admin/data',
    '/admin/maps/shegolskoe/edit',
    '/admin/items/create',
    '/me/items',
    '/me/routes',
]) {
    await step(`admin render ${url}`, async () => {
        await a.goto(BASE + url, { waitUntil: 'networkidle' });
        await a.waitForSelector('h1', { timeout: 5000 });
    });
}

await step('editor: place unplaced marker by clicking map', async () => {
    await a.goto(BASE + '/admin/maps/shegolskoe/editor', {
        waitUntil: 'networkidle',
    });
    await a
        .getByRole('button', { name: /Water tower/ })
        .first()
        .click();
    await a.getByRole('button', { name: /Place on map/ }).click();
    const box = await a.locator('.leaflet-container').boundingBox();
    await a.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
    await a.waitForSelector('.map-marker', { timeout: 5000 });
});
await a.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/editor.png`,
});

await step('public map shows placed marker; click opens panel', async () => {
    await a.goto(BASE + '/maps/shegolskoe', { waitUntil: 'networkidle' });
    await a.locator('.map-marker').first().click();
    await a.getByRole('button', { name: /Center/ }).waitFor({ timeout: 5000 });
});

await step('route: draw two points and save', async () => {
    await a.getByRole('button', { name: /Close details/ }).click();
    await a.getByRole('tab', { name: 'Mine' }).click();
    await a.getByRole('button', { name: /New route/ }).click();
    await a.getByPlaceholder('Route name').fill('Smoke route');
    const box = await a.locator('.leaflet-container').boundingBox();
    await a.mouse.click(box.x + 200, box.y + 200);
    await a.mouse.click(box.x + 400, box.y + 300);
    await a.getByRole('button', { name: 'Save route' }).click();
    await a.getByText('Route saved').waitFor({ timeout: 5000 });
});

await step('note: place a note', async () => {
    await a.getByRole('button', { name: /Place a note/ }).click();
    const box = await a.locator('.leaflet-container').boundingBox();
    await a.mouse.click(box.x + 300, box.y + 350);
    await a.getByLabel('Title').fill('Smoke note');
    await a.getByRole('button', { name: 'Save note' }).click();
    await a.getByText('Note saved').waitFor({ timeout: 5000 });
});
await a.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/map-mine.png`,
});

// Mobile
const mobile = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
});
const m = await page(mobile, 'mobile');
await step('mobile map renders', async () => {
    await m.goto(BASE + '/maps/factory', { waitUntil: 'networkidle' });
    await m.waitForSelector('.leaflet-container');
});
await m.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/mobile-map.png`,
});
await step('mobile item page', async () => {
    await m.goto(BASE + '/items/active-matter', { waitUntil: 'networkidle' });
    await m.waitForSelector('h1');
});
await m.screenshot({
    path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/mobile-item.png`,
    fullPage: true,
});

await browser.close();
console.log(results.join('\n'));
console.log(
    errors.length
        ? '\nERRORS:\n' + [...new Set(errors)].join('\n')
        : '\nNo JS errors.',
);
