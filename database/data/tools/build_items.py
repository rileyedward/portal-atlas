#!/usr/bin/env python3
"""
Builds database/data/items.json from docs/research-notes.md.

Item names and rarity tiers come verbatim from two sources recorded in the
research notes:
  * Appendix B: activematter.wiki.gg category listings (CC BY-SA 4.0).
  * Section 5: official edition/update item lists from activematter.game.

Nothing is invented: an item is only emitted if its exact name appears in one
of those lists. Run from the project root:

    python3 database/data/tools/build_items.py
"""

import json
import re
import unicodedata
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
NOTES = ROOT / "docs" / "research-notes.md"
OUT = ROOT / "database" / "data" / "items.json"

WIKI = "Active Matter Wiki (wiki.gg)"
OFFICIAL = "Active Matter official news"

# Primary category is the first match in this order; all wiki categories are
# kept as tags in metadata.
PRIORITY = [
    ("Keys", "keys", "Keys"),
    ("Contract items", "contract-items", "Contract items"),
    ("Remains", "remains", "Remains"),
    ("Safe containers", "safe-containers", "Safe containers"),
    ("Signal grenades", "signal-grenades", "Signal grenades"),
    ("Consumables", "consumables", "Consumables"),
    ("Medical items", "medical", "Medical"),
    ("Grenades", "grenades", "Grenades"),
    ("Armor plates", "armor-plates", "Armor plates"),
    ("Helmets", "helmets", "Helmets & headgear"),
    ("Backpacks", "backpacks", "Backpacks"),
    ("Pouches", "rigs", "Rigs & pouches"),
    ("Night Vision Devices", "optics-devices", "Night vision"),
    ("Flashlights", "flashlights", "Flashlights"),
    ("Magazines", "magazines", "Magazines & ammo"),
    ("Special", "special", "Special"),
    ("Park lab reagents", "chemicals", "Chemicals"),
    ("Chemicals", "chemicals", "Chemicals"),
    ("Electronics components", "electronic-components", "Electronic components"),
    ("Electronics", "electronics", "Electronics"),
    ("Industrial items", "industrial", "Industrial"),
    ("Tools", "tools", "Tools"),
    ("Kitchen items", "kitchen", "Kitchen"),
    ("Notes", "notes-documents", "Notes & documents"),
    ("Books", "notes-documents", "Notes & documents"),
    ("Newspapers", "notes-documents", "Notes & documents"),
    ("Vinyl record", "media", "Records & tapes"),
    ("VHS cassettes", "media", "Records & tapes"),
    ("Cassette tapes", "media", "Records & tapes"),
    ("Compact discs", "media", "Records & tapes"),
    ("Dogorsk: Timeline Collision", "event-items", "Event items"),
    ("Rusty", "rusty", "Rusty items"),
    ("Currencies", "currency", "Currency & resources"),
    ("Civil items", "civil", "Civil items"),
]

SKIP_CATEGORIES = {"Containers"}  # world containers, not inventory items
SKIP_NAMES = {"User:Prof. Sugarcube/Sandbox"}

# Official item names (section 5 of the notes), grouped by our category.
OFFICIAL_ITEMS = {
    ("weapons", "Weapons"): [
        "SOK-94 Vepr Carbine", "MB590 shotgun", "MP5", "M9 pistol", "Combat knife", "SV-98", "M4A1",
        "UMP45", "PKM", "M110A1", "SCAR-L", "SPAS-12", "Hunting crossbow", "Scorpion EVO 3", "MP-443",
        "War hammer", "M249 Para", "Origin-12", "RPG-7", "SVDM", "AEK-971", "AN-94", "PP-19-01",
        "M9 Tactical", "M24", "Stechkin", "PP-2000", "Gepard", "VSSK Vykhlop", "EV-MG", "Gravity gun",
        "RSh-12", "MAC-11", "P17-V", "SCAR-H", "M14", "M39 EMR", "DP-12", "Crowbar", "Pry bar",
        "Baseball bat", "Police Flashlight", "PPSh-41", "TT pistol", "M1928A1", "MP-40",
        "CF-400 Flamethrower",
    ],
    ("weapon-mods", "Weapon mods"): [
        "Scope PSO AK 4.0x", "1P69 Scope 1-10x", "EXPS3 Collimator", "Thermal Scope S350F 2.0x",
        "TNG6 Scope 1-6x", "TA31 Scope 4.0x", "Moosemark Collimator", "HX-QD Suppressor for M110A1",
        "BN45 Suppressor", "Mini 556 Suppressor", "BH-LGR03", "OM", "Blue Star", "DBAL-A2",
        "AN-PEQ-15", "Dlan-3", "4TK", "C5",
    ],
    ("equipment", "Equipment"): [
        "Small safe container", "Medium safe container", "Beacon", "Tactical beacon",
        "Small Backpack", "Medium Backpack", "Large Backpack", "Tactical PASGT Helmet", "A3 Helmet",
        "SF Helmet", "Flashlight", "First aid kit", "Big first aid kit", "Painkiller", "F-1 grenade",
        "Scout drone \"Harpy\"", "Kamikaze drone", "Thermal drone", "M8 Smoke grenade",
        "RGD-M Smoke grenade", "Streaming Injector", "Burst Injector", "Fire extinguisher",
        "EDH-Gen V", "TFMK-I", "Tank crew helmet", "PNV-57E NVD", "Tactical headset",
        "Stahlhelm", "M1 helmet",
    ],
    ("currency", "Currency & resources"): [
        "Active Matter", "Crystallised Active Matter", "Fracture Shard", "Data card", "Chronotraces",
    ],
}

OFFICIAL_NOTES = {
    "Active Matter": "Harvested by killing AM-transformed creatures, taken from other players, or picked up from Active Matter clusters.",
    "Crystallised Active Matter": "Premium-adjacent currency (CAM); also earned from special daily objectives and not purchasable with money.",
    "Chronotraces": "Crafting resource obtained by dismantling items. Six types: metal, composite, fiber, chemicals, energy, idea.",
    "Fracture Shard": "Found in \"timeline scars\" during the Dogorsk: Timeline Collision event.",
    "Data card": "Required to extract from Factory: Bloodbath (at least three).",
    "Gravity gun": "Raid-only weapon added with the Gigastructure update.",
}


def slugify(value: str) -> str:
    value = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode()
    value = re.sub(r"[^a-zA-Z0-9]+", "-", value).strip("-").lower()
    return value


def parse_appendix(text: str) -> dict:
    start = text.index("## Appendix B")
    items: dict[str, dict] = {}
    for line in text[start:].splitlines():
        m = re.match(r"- \*\*(.+?)\*\* \(\d+; (https://\S+?)\): (.+)$", line)
        if not m:
            continue
        category, url, rest = m.group(1), m.group(2), m.group(3)
        if category in SKIP_CATEGORIES:
            continue
        for entry in re.split(r";\s+(?=\S)", rest):
            em = re.match(r"(.+) \[(.+?)\]$", entry.strip())
            if not em:
                continue
            name, rarity = em.group(1).strip(), em.group(2).strip()
            if name in SKIP_NAMES:
                continue
            record = items.setdefault(name, {"categories": [], "rarity": None, "urls": []})
            record["categories"].append(category)
            record["urls"].append(url)
            if rarity not in ("?",) and "/" not in rarity:
                record["rarity"] = record["rarity"] or rarity
            elif "/" in rarity:
                record["rarity_note"] = f"Conflicting rarities reported: {rarity}"
    return items


def main() -> None:
    text = NOTES.read_text(encoding="utf-8")
    wiki = parse_appendix(text)

    categories: dict[str, str] = {}
    out: dict[str, dict] = {}

    for name, record in wiki.items():
        primary = next(((slug, label) for key, slug, label in PRIORITY if key in record["categories"]), ("civil", "Civil items"))
        categories[primary[0]] = primary[1]
        slug = slugify(name)
        item = {
            "slug": slug,
            "name": name,
            "category": primary[0],
            "rarity": record["rarity"],
            "source": WIKI,
            "source_url": "https://activematter.wiki.gg/wiki/" + name.replace(" ", "_").replace('"', "%22"),
            "source_note": "Name, category and rarity from activematter.wiki.gg category pages (CC BY-SA 4.0), pulled 2026-09-29.",
            "metadata": {"tags": sorted(set(record["categories"]))},
        }
        if "rarity_note" in record:
            item["metadata"]["rarity_note"] = record["rarity_note"]
        out[slug] = item

    for (cat_slug, cat_label), names in OFFICIAL_ITEMS.items():
        categories.setdefault(cat_slug, cat_label)
        for name in names:
            slug = slugify(name)
            existing = out.get(slug) or next((i for i in out.values() if i["name"].lower() == name.lower()), None)
            if existing:
                # Officially confirmed name; keep wiki rarity and category.
                existing["source"] = OFFICIAL
                existing["source_url"] = "https://activematter.game/en/news"
                existing["source_note"] = "Name confirmed in official Active Matter news/edition listings; rarity from activematter.wiki.gg (CC BY-SA 4.0)."
                if name in OFFICIAL_NOTES:
                    existing["description"] = OFFICIAL_NOTES[name]
                continue
            out[slug] = {
                "slug": slug,
                "name": name,
                "category": cat_slug,
                "rarity": None,
                "source": OFFICIAL,
                "source_url": "https://activematter.game/en/news",
                "source_note": "Name from official Active Matter news/edition listings. Rarity not documented in the source.",
                "metadata": {"tags": ["Official listing"]},
            }
            if name in OFFICIAL_NOTES:
                out[slug]["description"] = OFFICIAL_NOTES[name]

    payload = {
        "format": "active-matter-data/v1",
        "item_categories": [{"slug": s, "name": n} for s, n in sorted(categories.items(), key=lambda kv: kv[1])],
        "items": sorted(out.values(), key=lambda i: i["name"].lower()),
    }
    OUT.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {len(payload['items'])} items in {len(payload['item_categories'])} categories to {OUT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
