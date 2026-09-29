#!/usr/bin/env node
/**
 * Downloads the public JS data bundles from activematterhelp.ru and dumps the
 * marker, region, location, loot-table and key data (with English names
 * resolved by the site's own translation helpers) to JSON.
 *
 * Output: storage/app/private/activematterhelp/raw.json (git-ignored).
 * Then run: python3 database/data/tools/build_activematterhelp.py
 *
 * Usage: node database/data/tools/fetch_activematterhelp.mjs
 */
import { mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const BASE = 'https://activematterhelp.ru';
const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
const OUT = join(ROOT, 'storage/app/private/activematterhelp');
const CACHE = join(OUT, 'bundles');

rmSync(CACHE, { recursive: true, force: true });
mkdirSync(CACHE, { recursive: true });
writeFileSync(join(CACHE, 'package.json'), '{"type":"module"}');

const html = await (
    await fetch(`${BASE}/maps?map=dalniy`, {
        headers: { 'User-Agent': 'Mozilla/5.0' },
    })
).text();
const bundles = [...new Set(html.match(/\/assets\/[A-Za-z0-9_-]+\.js/g) ?? [])];
const wanted = ['data-markers', 'markerNaming', 'data-loot', 'keys'];
const files = {};

for (const path of bundles) {
    const name = path.split('/').pop();
    const prefix = wanted.find((w) => name.startsWith(`${w}-`));
    if (!prefix) continue;
    const body = await (await fetch(BASE + path)).text();
    writeFileSync(join(CACHE, name), body);
    files[prefix] = pathToFileURL(join(CACHE, name)).href;
}

for (const w of wanted) {
    if (!files[w])
        throw new Error(
            `Bundle ${w} not found — the site layout may have changed.`,
        );
}

const markers = await import(files['data-markers']);
const naming = await import(files.markerNaming);
const loot = await import(files['data-loot']);
const keys = await import(files.keys);

// Identify exports by shape rather than minified names.
const exported = Object.values(markers);
const allMarkers = exported.find(
    (v) => Array.isArray(v) && v[0]?.gameCoords && v.length > 1000,
);
const regions = exported.find((v) => Array.isArray(v) && v[0]?.gamePoints);
const locations = exported.find(
    (v) =>
        v &&
        !Array.isArray(v) &&
        typeof v === 'object' &&
        Array.isArray(v.dalniy) &&
        v.dalniy[0]?.bounds,
);
const translate = Object.values(naming).find(
    (f) =>
        typeof f === 'function' &&
        /[a-z]/i.test(f(allMarkers[0], 'en') ?? '') &&
        f(allMarkers[0], 'en') !== allMarkers[0].name,
);
const lootTables = Object.values(loot).find(
    (v) =>
        v &&
        typeof v === 'object' &&
        !Array.isArray(v) &&
        Object.values(v)[0]?.items,
);
const keyTable = Object.values(keys).find(
    (v) =>
        v &&
        typeof v === 'object' &&
        !Array.isArray(v) &&
        Object.values(v)[0]?.nameEn,
);

if (
    !allMarkers ||
    !regions ||
    !locations ||
    !translate ||
    !lootTables ||
    !keyTable
) {
    throw new Error('Could not identify one of the data exports.');
}

// The naming module's second helper translates descriptions.
const descHelper = Object.values(naming).find((f) => {
    if (typeof f !== 'function') return false;
    const sample = allMarkers.find((m) => m.description && !m.descriptionEn);
    try {
        const r = f(sample, 'en');
        return r && r !== sample.description && !/[Ѐ-ӿ]/.test(r);
    } catch {
        return false;
    }
});

const raw = {
    fetched_at: new Date().toISOString(),
    source: `${BASE}/maps`,
    locations,
    markers: allMarkers.map((m) => ({
        ...m,
        nameEn: translate(m, 'en'),
        descriptionEnResolved:
            m.descriptionEn ??
            (m.description && descHelper ? descHelper(m, 'en') : null),
    })),
    regions: regions.map((r) => ({ ...r, nameEn: translate(r, 'en') })),
    lootTables,
    keys: keyTable,
};

writeFileSync(join(OUT, 'raw.json'), JSON.stringify(raw));
console.log(
    `Saved ${raw.markers.length} markers, ${raw.regions.length} regions, ${Object.keys(lootTables).length} loot tables, ${Object.keys(keyTable).length} keys to ${join(OUT, 'raw.json')}`,
);
