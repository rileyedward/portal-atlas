import { chromium } from 'playwright';
const BASE = process.env.BASE_URL ?? 'http://active-matter-map.test';
const b = await chromium.launch();
const results = [];
const errs = [];
async function step(name, fn) {
    try {
        await fn();
        results.push('PASS ' + name);
    } catch (e) {
        results.push('FAIL ' + name + ': ' + e.message.split('\n')[0]);
    }
}
const guest = await b.newPage({ viewport: { width: 1400, height: 900 } });
guest.on('pageerror', (e) => errs.push(e.message));

await step('floating button sends general feedback', async () => {
    await guest.goto(BASE + '/items', { waitUntil: 'networkidle' });
    await guest.getByRole('button', { name: 'Send feedback' }).last().click();
    await guest.getByLabel("Something's broken").check();
    await guest
        .locator('#feedback-message')
        .fill('The item filter resets when I go back. (smoke test)');
    await guest.locator('#feedback-email').fill('tester@example.com');
    await guest.screenshot({
        path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/fb-dialog.png`,
    });
    await guest.getByRole('button', { name: 'Send feedback' }).last().click();
    await guest.getByText(/Your feedback was sent/).waitFor({ timeout: 5000 });
});

await step('item section icon opens prefilled form', async () => {
    await guest.goto(BASE + '/items/lucky-quarter', {
        waitUntil: 'networkidle',
    });
    await guest
        .getByRole('button', { name: /Lucky quarter \(Loot pools\)/ })
        .click();
    await guest.getByText('About: Loot pools').waitFor();
    await guest.keyboard.press('Escape');
});

await step('no source shown to guests in marker panel', async () => {
    await guest.goto(BASE + '/maps/factory', { waitUntil: 'networkidle' });
    await guest.locator('.map-marker').first().click();
    await guest.getByText('Data quality').waitFor();
    const panel = await guest
        .locator('aside[aria-label="Marker details"]')
        .innerText();
    if (/Source|activematterhelp|Active Matter Help/i.test(panel))
        throw new Error('source visible');
});

await step('suggest a new position by clicking the map', async () => {
    await guest
        .getByRole('button', { name: /Wrong spot\?|Suggest position/ })
        .click();
    await guest.getByText(/really is/).waitFor();
    const box = await guest.locator('.leaflet-container').boundingBox();
    await guest.mouse.click(box.x + box.width * 0.3, box.y + box.height * 0.4);
    await guest.getByText(/Suggested position x/).waitFor();
    await guest
        .locator('#feedback-message')
        .fill('It is a bit further west. (smoke test)');
    await guest.getByRole('button', { name: 'Send feedback' }).last().click();
    await guest.getByText(/Your feedback was sent/).waitFor({ timeout: 5000 });
});

await step('search with no results offers feedback', async () => {
    await guest.goto(BASE + '/', { waitUntil: 'networkidle' });
    await guest.keyboard.press('/');
    await guest.keyboard.type('zzqqxx');
    await guest.getByRole('button', { name: "Tell us what's missing" }).click();
    await guest.getByText('About: Search: zzqqxx').waitFor();
});

const admin = await b.newPage({ viewport: { width: 1400, height: 950 } });
admin.on('pageerror', (e) => errs.push(e.message));
await step('admin inbox shows feedback and applies a position', async () => {
    await admin.goto(BASE + '/login');
    await admin.getByLabel('Email address').fill('admin@test.com');
    await admin.getByLabel('Password', { exact: true }).fill('password');
    await admin.getByRole('button', { name: /Log in/i }).click();
    await admin.waitForURL((u) => !u.pathname.startsWith('/login'));
    await admin.goto(BASE + '/admin/reports', { waitUntil: 'networkidle' });
    await admin.getByText('The item filter resets').waitFor();
    await admin.getByText('tester@example.com').waitFor();
    await admin.screenshot({
        path: `${process.env.SCREENSHOT_DIR ?? '/tmp'}/fb-inbox.png`,
        fullPage: true,
    });
    admin.once('dialog', (d) => d.accept());
    await admin.getByRole('button', { name: 'Apply position' }).click();
    await admin
        .getByText(/and resolved the feedback/)
        .waitFor({ timeout: 5000 });
});

await step('admin sees the source in marker panel', async () => {
    await admin.goto(BASE + '/maps/factory', { waitUntil: 'networkidle' });
    await admin.locator('.map-marker').first().click();
    await admin.getByText('Data quality').waitFor();
    await admin
        .locator('aside[aria-label="Marker details"]')
        .getByText('Source')
        .waitFor();
});

await b.close();
console.log(results.join('\n'));
console.log('errors', errs);
