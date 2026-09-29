#!/usr/bin/env python3
"""
Converts the activematterhelp.ru dump (see fetch_activematterhelp.mjs) into this
app's import formats:

  database/data/activematterhelp/reference.json   active-matter-data/v1
      source, map calibration + variants, items, loot tables
  database/data/activematterhelp/maps/<slug>.json active-matter-map/v1
      positioned markers and area polygons, with variant, loot table and a
      stable external_ref for idempotent re-imports

Coordinates: the site stores game world positions as [z, x] ("coords").
Each raid ("location") has bounds; we pad them to include every point and
convert to the app's 0-100 percentage space (x from the left, y from the top,
north up). The bounds are kept in maps.metadata.calibration.

Run from the project root after fetching:

    node database/data/tools/fetch_activematterhelp.mjs
    python3 database/data/tools/build_activematterhelp.py
"""

import json
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
RAW = ROOT / "storage" / "app" / "private" / "activematterhelp" / "raw.json"
OUT = ROOT / "database" / "data" / "activematterhelp"
OUR_ITEMS = ROOT / "database" / "data" / "items.json"

SOURCE = "Active Matter Help (activematterhelp.ru)"
SOURCE_URL = "https://activematterhelp.ru/maps"
PAD = 0.03

# Site location id -> (our map slug, forced variant)
LOCATIONS = {
    "ozernoe": ("ozernoe", None),
    "schegolskoe": ("shegolskoe", None),
    "svalka": ("scrapyard", None),
    "voennaya_baza": ("military-base", None),
    "damba": ("dam", None),
    "port": ("cargo-port", None),
    "zavod": ("factory", None),
    "Dogorsk": ("dogorsk", None),
    "Park": ("park", None),
    "city_center": ("downtown", None),
    "city_collapse": ("downtown", "collapse"),
    "hq": ("headquarters", None),
    "airport": ("airport", None),
}
SITE_MAP = {"dalniy": "dalniy", "forsaken_africa": "forsaken_africa", "america_abandoned": "america_abandoned"}

VARIANT_LABELS = {
    "regular": "Regular",
    "overgrowth": "Overgrowth",
    "fire": "Inferno",
    "distortion": "Distortion",
    "dark": "Darkness",
    "deep_cover": "Deep Cover",
    "bloodbath": "Bloodbath",
    "hive": "Hive",
    "purge": "Support Protocol",
    "purge_distortion": "Support Protocol (Distortion)",
    "mothman": "Mothman",
    "timeline": "Timeline Collision",
    "escape": "Escape (PvE)",
    "br": "Unstable Zone",
    "collapse": "Collapse (PvE)",
}
REGULAR_ALIASES = {"regular", "adv", "statues"}

TYPES = {
    "extraction_always": "extraction-point",
    "extraction_time": "dynamic-extraction",
    "extraction_final": "final-extraction",
    "spawn": "spawn",
    "spawn_zone": "spawn-zone",
    "spawn_car": "vehicle-spawn",
    "portal": "portal",
    "locked_doors": "locked-door",
    "floor_loot": "loot-location",
    "t2_box": "container-tier-2",
    "t3_box": "container-tier-3",
    "medical": "medical-supplies",
    "ammo_box": "ammo-box",
    "safe": "safe",
    "artifact": "artifact",
    "documents": "documents",
    "key": "key-spawn",
    "phone": "interactive",
    "coin": "interactive",
    "interactive": "interactive",
    "question": "interactive",
    "bunker": "landmark",
    "contract_device": "contract",
    "contract_container": "contract",
    "contract_car": "contract",
    "contract_car_dest": "contract",
    "puzzle_remains": "puzzle",
    "puzzle_safe": "puzzle",
    "puzzle_battery": "puzzle",
    "puzzle_vault": "puzzle",
    "puzzle_catalyst": "puzzle",
    "monster": "monster",
    "mimic": "monster",
    "boss": "boss",
    "anomaly": "anomaly",
}

# Loot table title prefix ("Factory [Adv.] · Shelves") -> our map slug.
TABLE_PREFIXES = [
    ("Military Base", "military-base"),
    ("America", "downtown"),
    ("Abandoned America", "downtown"),
    ("HQ", "headquarters"),
    ("Airport", "airport"),
    ("Dogorsk", "dogorsk"),
    ("Factory", "factory"),
    ("Shegolskoe", "shegolskoe"),
    ("Gigastructure", "gigastructure"),
    ("Cargo Port", "cargo-port"),
    ("Ozernoe", "ozernoe"),
    ("Dam", "dam"),
    ("Scrapyard", "scrapyard"),
    ("Park", "park"),
]


def table_map(title):
    if " · " not in (title or ""):
        return None
    prefix = title.split(" · ")[0]
    for name, slug in TABLE_PREFIXES:
        if prefix.startswith(name):
            return slug
    return None


MANUAL_NAMES = {"Охраняемая зона": "Guarded zone", "Зона искажения: Альфа": "Distortion zone: Alpha"}
CYRILLIC = re.compile(r"[Ѐ-ӿ]")


def slugify(value: str) -> str:
    value = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-zA-Z0-9]+", "-", value).strip("-").lower()[:140]


def norm(value: str) -> str:
    return re.sub(r"[^a-z0-9]+", " ", value.lower()).strip()


def variant_of(mod, forced=None):
    if forced:
        return forced
    if mod is None:
        return None
    return "regular" if mod in REGULAR_ALIASES else mod


def english(name_en, name_ru):
    if name_en and not CYRILLIC.search(name_en):
        return name_en
    return MANUAL_NAMES.get(name_ru, name_en or name_ru)


def chance(value):
    if value is None:
        return None
    m = re.match(r"\s*([\d.]+)", str(value))
    return round(float(m.group(1)), 3) if m else None


def main() -> None:
    raw = json.loads(RAW.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)
    (OUT / "maps").mkdir(exist_ok=True)

    # ---------------------------------------------------------------- bounds
    bounds = {}
    for site_map, locs in raw["locations"].items():
        for loc in locs:
            if loc["id"] not in LOCATIONS:
                continue
            slug = LOCATIONS[loc["id"]][0]
            (a, b) = loc["bounds"]
            lat = [a[0], b[0]]
            lng = [a[1], b[1]]
            box = bounds.setdefault(slug, {"lat": [min(lat), max(lat)], "lng": [min(lng), max(lng)], "site_map": site_map})
            box["lat"] = [min(box["lat"][0], *lat), max(box["lat"][1], *lat)]
            box["lng"] = [min(box["lng"][0], *lng), max(box["lng"][1], *lng)]

    markers_by_map = defaultdict(list)
    for m in raw["markers"]:
        if m["locationId"] in LOCATIONS:
            markers_by_map[LOCATIONS[m["locationId"]][0]].append(m)

    # Assign regions (no location id) to the map whose bounds contain their centroid.
    regions_by_map = defaultdict(list)
    for r in raw["regions"]:
        pts = r["points"]
        clat = sum(p[0] for p in pts) / len(pts)
        clng = sum(p[1] for p in pts) / len(pts)
        for slug, box in bounds.items():
            if SITE_MAP.get(r["map"]) == box["site_map"] and box["lat"][0] <= clat <= box["lat"][1] and box["lng"][0] <= clng <= box["lng"][1]:
                regions_by_map[slug].append(r)
                break

    # Grow bounds so every point fits, then pad.
    for slug, box in bounds.items():
        lats = [m["coords"][0] for m in markers_by_map[slug]] + [p[0] for r in regions_by_map[slug] for p in r["points"]]
        lngs = [m["coords"][1] for m in markers_by_map[slug]] + [p[1] for r in regions_by_map[slug] for p in r["points"]]
        lat0, lat1 = min(box["lat"][0], *lats), max(box["lat"][1], *lats)
        lng0, lng1 = min(box["lng"][0], *lngs), max(box["lng"][1], *lngs)
        pl, pg = (lat1 - lat0) * PAD, (lng1 - lng0) * PAD
        box["lat"] = [round(lat0 - pl, 2), round(lat1 + pl, 2)]
        box["lng"] = [round(lng0 - pg, 2), round(lng1 + pg, 2)]

    def to_pct(slug, lat, lng):
        box = bounds[slug]
        x = (lng - box["lng"][0]) / (box["lng"][1] - box["lng"][0]) * 100
        y = (box["lat"][1] - lat) / (box["lat"][1] - box["lat"][0]) * 100
        return round(min(100, max(0, x)), 4), round(min(100, max(0, y)), 4)

    # ---------------------------------------------------------------- items
    ours = json.loads(OUR_ITEMS.read_text(encoding="utf-8"))["items"]
    ours_by_norm = {norm(i["name"]): i for i in ours}
    used_slugs = {i["slug"] for i in ours}
    item_rows = {}  # norm name -> import row
    table_rows = []
    keys = raw["keys"]

    def item_slug_for(name_en, entry):
        n = norm(name_en)
        if n in item_rows:
            return item_rows[n]["slug"]
        existing = ours_by_norm.get(n)
        metadata = {k: v for k, v in {
            "chronotraces": entry.get("traces"),
            "active_matter": entry.get("am"),
            "volume": entry.get("vol"),
        }.items() if v is not None}
        if existing:
            row = {"slug": existing["slug"], "name": existing["name"]}
            if entry.get("credits") is not None:
                row["value"] = int(entry["credits"])
            if not existing.get("rarity") and entry.get("rarity"):
                row["rarity"] = entry["rarity"].capitalize()
            if metadata:
                row["metadata"] = {**existing.get("metadata", {}), **metadata}
        else:
            slug = slugify(name_en) or "item"
            base, i = slug, 2
            while slug in used_slugs:
                slug, i = f"{base}-{i}", i + 1
            used_slugs.add(slug)
            row = {
                "slug": slug,
                "external_ref": "amh:" + n.replace(" ", "-"),
                "name": name_en,
                "rarity": entry["rarity"].capitalize() if entry.get("rarity") else None,
                "value": int(entry["credits"]) if entry.get("credits") is not None else None,
                "source": SOURCE,
                "source_url": "https://activematterhelp.ru/items",
                "source_note": "Imported from activematterhelp.ru loot tables.",
                "metadata": metadata or None,
            }
            row = {k: v for k, v in row.items() if v is not None}
        item_rows[n] = row
        return row["slug"]

    for key, table in raw["lootTables"].items():
        entries = []
        seen = set()
        for entry in table.get("items", []):
            name_en = entry.get("nameEn")
            if not name_en or CYRILLIC.search(name_en):
                continue
            slug = item_slug_for(name_en, entry)
            if slug in seen:
                continue
            seen.add(slug)
            entries.append({k: v for k, v in {"item": slug, "chance": chance(entry.get("chance"))}.items() if v is not None})
        if not entries:
            continue
        title = table.get("titleEn") or table.get("title") or key
        row = {"key": key, "name": title, "source": SOURCE, "items": entries}
        if table_map(title):
            row["metadata"] = {"map": table_map(title)}
        table_rows.append(row)

    # Key items referenced by doors/safes.
    key_names = {k: (v.get("nameEn") or v.get("name")) for k, v in keys.items()}

    # ---------------------------------------------------------------- maps
    ref_maps = []
    stats = {}
    for slug, box in sorted(bounds.items()):
        width = max(600, round((box["lng"][1] - box["lng"][0]) * 2))
        height = max(600, round((box["lat"][1] - box["lat"][0]) * 2))
        rows = []
        variants = Counter()
        used_refs = Counter()

        def unique_ref(raw_id):
            # The site reuses some ids within a map; suffix repeats deterministically.
            used_refs[raw_id] += 1
            n = used_refs[raw_id]
            return f"amh:{raw_id}" if n == 1 else f"amh:{raw_id}~{n}"

        for m in markers_by_map[slug]:
            forced = LOCATIONS[m["locationId"]][1]
            variant = variant_of(m.get("mod"), forced)
            if variant:
                variants[variant] += 1
            x, y = to_pct(slug, m["coords"][0], m["coords"][1])
            name = english(m.get("nameEn"), m["name"])
            description = m.get("descriptionEnResolved")
            conditions = []
            if m.get("key") and m["key"] in key_names:
                conditions.append(f"Requires: {key_names[m['key']]}")
            if m.get("keyid") and m["keyid"] in key_names:
                description = f"Key: {key_names[m['keyid']]}"
            if m.get("item"):
                conditions.append(f"Item: {m['item']}")
            metadata = {
                "game_coords": m.get("gameCoords"),
                "site_type": m["type"],
            }
            if m.get("zone"):
                z = m["zone"]
                spawns = ", ".join(f"{s.get('en')} {s.get('pct')}%" for s in z.get("spawns", []) if s.get("en"))
                description = f"Radius {z.get('radius')} m, up to {z.get('maxGuards')} guards. Spawns: {spawns}" if spawns else description
            if m.get("mode"):
                metadata["mode"] = m["mode"]
            if m.get("group"):
                metadata["group"] = m["group"]
            if conditions:
                metadata["conditions"] = "; ".join(conditions)
            row = {
                "external_ref": unique_ref(m["id"]),
                "type": TYPES.get(m["type"], "landmark"),
                "name": name[:255],
                "description": description,
                "x": x,
                "y": y,
                "variant": variant,
                "loot_table": m.get("loot"),
                "source": SOURCE,
                "source_url": SOURCE_URL,
                "metadata": {k: v for k, v in metadata.items() if v is not None},
            }
            rows.append({k: v for k, v in row.items() if v is not None})

        for r in regions_by_map[slug]:
            variant = variant_of(r.get("mod"))
            if variant:
                variants[variant] += 1
            geometry = [list(to_pct(slug, p[0], p[1])) for p in r["points"]]
            cx = round(sum(p[0] for p in geometry) / len(geometry), 4)
            cy = round(sum(p[1] for p in geometry) / len(geometry), 4)
            row = {
                "external_ref": unique_ref(r["id"]),
                "type": "area",
                "name": english(r.get("nameEn"), r["name"])[:255],
                "description": r.get("desc"),
                "x": cx,
                "y": cy,
                "geometry": geometry,
                "variant": variant,
                "loot_table": r.get("loot"),
                "source": SOURCE,
                "source_url": SOURCE_URL,
            }
            rows.append({k: v for k, v in row.items() if v is not None})

        options = [{"key": k, "label": VARIANT_LABELS.get(k, k.replace("_", " ").title()), "count": c}
                   for k, c in sorted(variants.items(), key=lambda kv: (kv[0] != "regular", kv[0]))]
        ref_maps.append({
            "slug": slug,
            "width": width,
            "height": height,
            "metadata": {
                "calibration": {"lat": box["lat"], "lng": box["lng"], "site_map": box["site_map"], "source": SOURCE},
                "variant_options": options,
            },
        })
        (OUT / "maps" / f"{slug}.json").write_text(json.dumps({
            "format": "active-matter-map/v1",
            "map": slug,
            "markers": rows,
        }, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
        stats[slug] = len(rows)

    reference = {
        "format": "active-matter-data/v1",
        "sources": [{
            "name": SOURCE,
            "kind": "community_tool",
            "url": SOURCE_URL,
            "reliability": 70,
            "notes": "Fan site dataset (positions, loot tables, keys). © Active Matter Help; imported at the project owner's request. Positions appear to be derived from game-world coordinates.",
        }],
        "maps": ref_maps,
        "items": list(item_rows.values()),
        "loot_tables": table_rows,
    }
    (OUT / "reference.json").write_text(json.dumps(reference, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")

    new_items = sum(1 for r in item_rows.values() if "external_ref" in r)
    print(f"maps: {stats}")
    print(f"markers total: {sum(stats.values())}; items: {len(item_rows)} ({new_items} new); loot tables: {len(table_rows)}")


if __name__ == "__main__":
    main()
