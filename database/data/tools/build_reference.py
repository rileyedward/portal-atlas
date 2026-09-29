#!/usr/bin/env python3
"""
Builds the hand-curated reference data (versions, sources, maps, objectives)
and per-map marker datasets from facts recorded in docs/research-notes.md.

Every marker here is UNPLACED (no x/y): sources describe places in words but
no source publishes coordinates. Editors position them later from their own
gameplay observation in the admin map editor.

Run from the project root:

    python3 database/data/tools/build_reference.py
"""

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
DATA = ROOT / "database" / "data"
NEWS = "https://activematter.game/en/news/"
WIKI = "https://activematter.wiki.gg/wiki/"

OFFICIAL = "Active Matter official news"
FAQ = "Active Matter official FAQ"
WIKIGG = "Active Matter Wiki (wiki.gg)"
FANDOM_EN = "Active Matter Wiki (Fandom, EN)"
FANDOM_RU = "Active Matter Wiki (Fandom, RU)"

reference = {
    "format": "active-matter-data/v1",
    "versions": [
        {"version": "0.1.0", "name": "Early Version", "released_at": "2025-09-09", "source_url": NEWS + "28",
         "notes": "PC early version via Gaijin launcher."},
        {"version": "0.2.1", "name": "Fire Walk", "released_at": "2025-12-18", "source_url": NEWS + "104",
         "notes": "Added Dam; Dogorsk opened to open play."},
        {"version": "0.3.0", "name": "Gigastructure", "released_at": "2026-04-01", "source_url": NEWS + "192",
         "notes": "Added Gigastructure and Park."},
        {"version": "0.4.0", "name": "250 Shades of Liberty", "released_at": "2026-08-27", "source_url": NEWS + "291",
         "notes": "Added Downtown (America) and Airport (Africa). Full release on 2026-09-15 (news/318)."},
        {"version": "0.4.0.156", "name": "Build 0.4.0.156", "released_at": "2026-09-26", "is_current": True,
         "source_url": NEWS + "339", "notes": "Latest build at research time. Builds ship every few days — update from the admin panel."},
    ],
    "sources": [
        {"name": OFFICIAL, "kind": "official", "url": "https://activematter.game/en/news", "reliability": 95,
         "notes": "Official news, patch notes and developer Q&As."},
        {"name": FAQ, "kind": "official", "url": "https://activematter.game/en/faq", "reliability": 95},
        {"name": WIKIGG, "kind": "community_wiki", "url": "https://activematter.wiki.gg/", "reliability": 55,
         "notes": "Best English community wiki. Content licensed CC BY-SA 4.0; attribution required. Some copy-paste errors observed."},
        {"name": FANDOM_EN, "kind": "community_wiki", "url": "https://activemattergame.fandom.com/", "reliability": 30,
         "notes": "Low quality, partly vandalised. Use only for corroboration."},
        {"name": FANDOM_RU, "kind": "community_wiki", "url": "https://active-matter.fandom.com/ru/", "reliability": 40,
         "notes": "Russian wiki with small interactive maps (CC BY-SA)."},
    ],
    "maps": [],
    "objectives": [],
}

# ---------------------------------------------------------------------------
# Maps. `facts` are sourced statements shown in the map's "Intel" tab.
# ---------------------------------------------------------------------------

def fact(text, url, confidence="High"):
    return {"text": text, "source_url": url, "confidence": confidence}


MAPS = [
    dict(slug="shegolskoe", name="Shegolskoe", summary="Village on the southern edge of Dalniy Island, partly torn from the ground by gravitational anomalies.",
         source="Active Matter official news", source_url=NEWS + "223",
         region="Dalniy Island", variants=["Overgrowth", "Distortion"],
         facts=[fact("Located on the southern edge of Dalniy Island.", NEWS + "223"),
                fact("Houses #3 and #6 are suspended by gravity anomalies and reached via portals in houses #9 and #8.", WIKI + "Shegolskoe", "Medium"),
                fact("Only three abnormal signals, each in the same location every raid.", WIKI + "Shegolskoe", "Medium"),
                fact("Overgrowth adds Flowermen and Dendroids; Distortion adds Distorted, Mimics, Hellhounds and the Alpha Mimic.", WIKI + "Shegolskoe", "Medium")]),
    dict(slug="ozernoe", name="Ozernoe", summary="Lakeside village with an observation deck, power plant, church and a contested fire station.",
         source=OFFICIAL, source_url=NEWS + "13", region="Dalniy Island", variants=["Overgrowth", "Firestorm (limited-time)"],
         facts=[fact("The observation deck, power plant and village church are by the lakeside; these areas have sniper positions.", NEWS + "13"),
                fact("The fire station holds loot but sees high competition.", NEWS + "13"),
                fact("Seven abnormal signals.", WIKI + "Ozernoe", "Medium"),
                fact("Spawns in the northern portion by the bus station or power station and lake, or south of the village.", WIKI + "Ozernoe", "Medium"),
                fact("Located northeast of the Dogorsk raid.", WIKI + "Ozernoe", "Medium")]),
    dict(slug="factory", name="Factory", summary="Industrial complex (Malie Chelni) with six workshops, a construction site and a power station.",
         source=WIKIGG, source_url=WIKI + "Factory", region="Dalniy Island", variants=["Hive", "Bloodbath (overtime)"],
         facts=[fact("Also known as Malie Chelni; a road bisects the map north/south.", WIKI + "Factory", "Medium"),
                fact("Six abnormal signals.", WIKI + "Factory", "Medium"),
                fact("Enemies reported: Devourers, Flowermen, Distorted, Invisibles, Turned Soldiers, ball lightning.", WIKI + "Factory", "Medium"),
                fact("Factory: Bloodbath requires at least three data cards to extract.", NEWS + "91"),
                fact("In-game valuable-loot descriptions were added for this raid.", NEWS + "315")]),
    dict(slug="dogorsk", name="Dogorsk", summary="Dalniy Island town with dynamic extraction points.",
         source=OFFICIAL, source_url=NEWS + "104", region="Dalniy Island", variants=["Timeline Collision (overtime)"],
         facts=[fact("Dynamic extraction points were added in 0.4.0.122.", NEWS + "324"),
                fact("Alpha Mimic and Dendroids can spawn here.", NEWS + "192"),
                fact("Timeline Collision: zones marked by wrecked WWII-era vehicles contain timeline scars with Fracture Shards.", NEWS + "237")]),
    dict(slug="cargo-port", name="Cargo Port", summary="Northeastern port with floating barges around a colossal gravity anomaly.",
         source=OFFICIAL, source_url=NEWS + "45", region="Dalniy Island", variants=["Darkness", "Deep Cover (solo overtime)"],
         facts=[fact("Locked barns and flying ships.", NEWS + "45"),
                fact("The crane is locked and requires a key.", NEWS + "206"),
                fact("Static barges surround an active matter clump.", NEWS + "270", "Medium"),
                fact("Northeast of Dalniy Island per the community island layout.", WIKI + "Dalniy_Island", "Medium")]),
    dict(slug="dam", name="Dam", summary="Dam and administration building holding high-value gear; a submarine is frozen in space nearby.",
         source=OFFICIAL, source_url=NEWS + "223", region="Dalniy Island",
         facts=[fact("Inside the dam and its administration building players can find high-value gear.", NEWS + "223"),
                fact("A final extraction point appears after the zone's collapse phase.", NEWS + "320"),
                fact("Shy Girl is listed among the Dam's anomalies.", NEWS + "142")]),
    dict(slug="park", name="Park", summary="The greenest spot on Dalniy Island, overrun with Flowermen and Dendroids; home of the greenhouse lab.",
         source=OFFICIAL, source_url=NEWS + "171", region="Dalniy Island",
         facts=[fact("Lies between Shegolskoe and Dogorsk.", NEWS + "171"),
                fact("Overrun with Flowermen and Dendroids; the Alpha Flowerman boss was introduced with this update.", NEWS + "192"),
                fact("The underground lab is called Sector Flora.", WIKI + "Park", "Medium")]),
    dict(slug="military-base", name="Military Base", summary="Base with impressive stockpiles of rare firearms and destructible wooden crates.",
         source=OFFICIAL, source_url=NEWS + "14", region="Dalniy Island",
         region_note="Region not officially confirmed.", variants=["Unstable Zone", "Silent Observers"],
         facts=[fact("Impressive stockpiles of rare firearms.", NEWS + "14"),
                fact("Unstable Zone variant has final zone-contraction locations.", NEWS + "327")]),
    dict(slug="scrapyard", name="Scrapyard", summary="Vehicle graveyard and industrial dump, played only at night in heavy fog.",
         source=WIKIGG, source_url=WIKI + "Scrapyard", region="Dalniy Island",
         region_note="Region not officially confirmed.", variants=["Midnight"],
         facts=[fact("Only playable at night with extreme fog; filled with Distorted. A thermal scope dissipates the fog.", WIKI + "Scrapyard", "Medium"),
                fact("Agents spawn around the exterior and fight inwards to the lone central extraction portal.", WIKI + "Scrapyard", "Medium"),
                fact("Three abnormal signals, surrounded by gravity traps; roaming ball lightning; Invisibles.", WIKI + "Scrapyard", "Medium")]),
    dict(slug="headquarters", name="Headquarters", summary="Sweltering city of military bases, command centers, civilian blocks and an airfield.",
         source=OFFICIAL, source_url=NEWS + "223",
         region_note="Region unconfirmed (possibly Anguka Anga / Africa).",
         variants=["Unstable Zone", "Code 2901 (solo overtime)", "Assistance Protocol (overtime)"],
         facts=[fact("Military bases, command centers, civilian blocks and an airfield, patrolled by Turned Soldiers and monsters.", NEWS + "223"),
                fact("Military facilities now always have weapons.", NEWS + "223", "Medium"),
                fact("Hellhounds and Dendroids can spawn here.", NEWS + "336")]),
    dict(slug="airport", name="Airport", summary="African raid: hangars with military gear and a plane suspended in a gravity anomaly.",
         source=OFFICIAL, source_url=NEWS + "291", region="Africa (Anguka Anga)",
         facts=[fact("Search hangars with military gear and a plane suspended in a gravity anomaly.", NEWS + "291"),
                fact("Hangar 8A interior access is now key-gated (wall hole sealed).", NEWS + "339")]),
    dict(slug="downtown", name="Downtown", summary="Abandoned North American city: avenues, ruined skyscrapers, school buses and taxis.",
         source=OFFICIAL, source_url=NEWS + "291", region="America (Saltriver City per community sources)", variants=["Unstable Zone", "Collapse"],
         facts=[fact("Avenues, ruined skyscrapers, school buses and taxis.", NEWS + "291"),
                fact("Police and SWAT patrols, plus an American Flowermen variant with its own Alpha.", NEWS + "291"),
                fact("Raid duration was raised from 30 to 45 minutes.", NEWS + "303"),
                fact("Non-playable floors and rooftops are blocked by barriers.", NEWS + "298", "Medium")]),
    dict(slug="gigastructure", name="Gigastructure", summary="Soviet-era building stranded between worlds; cyclical iterations end in a purge. Open raids only.",
         source=OFFICIAL, source_url=NEWS + "193",
         facts=[fact("Occupying an elevator is the only confirmed way to survive a purge phase.", NEWS + "193"),
                fact("Survivors reach a lobby with available extraction points and the option to re-enter; risk escalates with each re-entry.", NEWS + "193"),
                fact("Total loss of all collected loot upon death.", NEWS + "193"),
                fact("Access was unlocked through the \"Gigastructure: Entry Protocol\" investigation.", WIKI + "Gigastructure", "Medium"),
                fact("Extraction portal opens in 5 seconds (20 seconds elsewhere).", WIKI + "Gigastructure", "Medium")]),
]

for index, m in enumerate(MAPS):
    reference["maps"].append({
        "slug": m["slug"],
        "name": m["name"],
        "summary": m["summary"],
        "status": "published",
        "width": 1000,
        "height": 1000,
        "sort_order": index,
        "version": "0.4.0.156",
        "source": m["source"],
        "source_url": m["source_url"],
        "metadata": {k: v for k, v in {
            "region": m.get("region"),
            "region_note": m.get("region_note"),
            "variants": m.get("variants"),
            "facts": m.get("facts"),
        }.items() if v},
    })

# ---------------------------------------------------------------------------
# Objectives (only those with a verifiable name + description in a source).
# ---------------------------------------------------------------------------
OBJECTIVES = {
    "format": "active-matter-data/v1",
    "objectives": [
        {"slug": "active-matter-collection", "name": "Active Matter Collection", "kind": "objective", "map": "shegolskoe",
         "description": "Primary objective: collect 15 Active Matter.", "source": WIKIGG, "source_url": WIKI + "Shegolskoe",
         "source_note": "Listed as the Shegolskoe primary objective on wiki.gg.",
         "items": [{"item": "active-matter", "quantity": 15, "role": "required"}]},
        {"slug": "gigastructure-entry-protocol", "name": "Gigastructure: Entry Protocol", "kind": "investigation", "map": "gigastructure",
         "description": "Investigation that unlocks access to the Gigastructure raid.", "source": WIKIGG, "source_url": WIKI + "Gigastructure"},
        {"slug": "harbor-anomaly-entrance", "name": "Harbor Anomaly: Entrance", "kind": "objective", "map": "cargo-port",
         "description": "In-game objective text: \"A colossal gravitation anomaly ... There is a portal on one of the barges near the shore\".",
         "source": WIKIGG, "source_url": WIKI + "Cargo_Port"},
    ],
}

# ---------------------------------------------------------------------------
# Markers per map. (type, name, description, source, url, extra)
# ---------------------------------------------------------------------------

def marker(type_, name, description, source, url, **extra):
    data = {"type": type_, "name": name, "description": description, "source": source, "source_url": url}
    data.update(extra)
    return data


MARKERS = {
    "shegolskoe": [
        marker("extraction-point", "Extraction portal (southern Euclid anomaly)",
               "The lone static extraction portal sits at the top of a southern gravity anomaly known as a \"Euclid\". Occasionally, other extraction portals appear.",
               WIKIGG, WIKI + "Shegolskoe", metadata={"conditions": "Works until the raid timer runs out."}),
        marker("landmark", "Water tower", "Offers an excellent view over the village.", OFFICIAL, NEWS + "223"),
        marker("area", "Floating houses", "Gravitational anomalies have ripped part of the settlement from the ground; some floating houses are only reachable through portals in other buildings.", OFFICIAL, NEWS + "223"),
        marker("building", "House #3 (suspended)", "Suspended by a gravity anomaly; reached through a portal in house #9.", WIKIGG, WIKI + "Shegolskoe"),
        marker("building", "House #6 (suspended)", "Suspended by a gravity anomaly; reached through a portal in house #8.", WIKIGG, WIKI + "Shegolskoe"),
        marker("building", "Three-story apartment block", "North of the village. Contains a surveillance room.", WIKIGG, WIKI + "Shegolskoe"),
        marker("building", "House #7", "Contains a surveillance room.", WIKIGG, WIKI + "Shegolskoe"),
        marker("landmark", "Radio tower", "In the wooded area to the southwest.", WIKIGG, WIKI + "Shegolskoe"),
        marker("landmark", "Downed helicopter", "By the lake and northern bridge to the west.", WIKIGG, WIKI + "Shegolskoe"),
        marker("container", "Stash: trailer by the radio tower", "Inside the trailer next to the southern radio tower.", WIKIGG, WIKI + "Shegolskoe"),
        marker("container", "Stash: apartment balcony", "On the balcony of the top apartment on the eastern portion of the three-story building.", WIKIGG, WIKI + "Shegolskoe"),
        *[marker("container", f"House #{n} attic", f"Locked attic opened by the \"Shegolskoe {n} attic key\".", WIKIGG, WIKI + "Shegolskoe")
          for n in (4, 8, 11, 15)],
        marker("anomaly", "Ball lightning", "Reported inside buildings in the regular setting.", WIKIGG, WIKI + "Shegolskoe"),
    ],
    "ozernoe": [
        marker("extraction-point", "Extraction portal (administration building)", "An extraction portal is adjacent to a room of the administration building.", WIKIGG, WIKI + "Ozernoe"),
        marker("landmark", "Observation deck", "By the lakeside; sniper positions.", OFFICIAL, NEWS + "13"),
        marker("building", "Power plant", "By the lakeside; sniper positions. There is a medical cache at the power substation.", OFFICIAL, NEWS + "13"),
        marker("building", "Village church", "By the lakeside. Swarms have been reported here; can be abseiled via a gravity anomaly on the western wall.", OFFICIAL, NEWS + "324"),
        marker("loot-location", "Fire station", "Loot with high competition. Compound includes a water tower and garage.", OFFICIAL, NEWS + "13"),
        marker("container", "Stash: fire station substation", "Inside a substation structure on the northeastern corner of the fire station compound.", WIKIGG, WIKI + "Ozernoe"),
        marker("area", "Sylvan glade", None, OFFICIAL, NEWS + "13"),
        marker("landmark", "Lake Tikhoe", None, WIKIGG, WIKI + "Ozernoe"),
        marker("building", "Bus station", "Northern spawn area.", WIKIGG, WIKI + "Ozernoe"),
        marker("building", "Post office", None, WIKIGG, WIKI + "Ozernoe"),
        marker("building", "Shop", None, WIKIGG, WIKI + "Ozernoe"),
        marker("building", "Administration building", "Adjacent to the extraction portal.", WIKIGG, WIKI + "Ozernoe"),
        marker("enemy", "Swarms (church)", "Swarms reported at the church.", OFFICIAL, NEWS + "324"),
        marker("enemy", "Turned soldiers (fire station, southern road, village)", "Turned soldiers guard the fire station, the southern road and the village.", WIKIGG, WIKI + "Ozernoe"),
    ],
    "factory": [
        marker("extraction-point", "Northwestern extraction portal (construction site)", "Extraction portal in the construction site, which also has gravity anomalies.", WIKIGG, WIKI + "Factory"),
        marker("extraction-point", "Southern extraction portal (Euclid anomaly)", "In Malie Chelni, on a Euclid anomaly south of the factory complex.", WIKIGG, WIKI + "Factory"),
        *[marker("building", f"Factory #{n}", "One of the main factory workshops." + (" The ship \"Severniy Veter\" rests on its roof." if n == 3 else ""), WIKIGG, WIKI + "Factory")
          for n in range(1, 6)],
        marker("landmark", "Ship \"Severniy Veter\"", "On the roof of Factory #3.", WIKIGG, WIKI + "Factory"),
        marker("building", "Gas station", "Outside the factory. Shy Girl has been reported here.", WIKIGG, WIKI + "Factory"),
        marker("building", "Dormitory", "In Malie Chelni, south of the factory complex.", WIKIGG, WIKI + "Factory"),
        marker("area", "Malie Chelni", "Area south of the factory complex with a dormitory, a park, garages and a Euclid anomaly.", WIKIGG, WIKI + "Factory"),
        marker("building", "Power station complex", "East of the dormitory.", WIKIGG, WIKI + "Factory"),
        marker("building", "Hangar #17", "South of the power station complex. Opened by the Hangar 17 key.", WIKIGG, WIKI + "Factory"),
        marker("building", "Hangar #18", "South of the power station complex. Opened by the Hangar 18 key.", WIKIGG, WIKI + "Factory"),
        marker("enemy-camp", "Power station chimney hive", "Swarm hive on the power station chimney.", WIKIGG, WIKI + "Factory"),
        marker("landmark", "Fishing installations", "Two small fishing installations along the southern coast.", WIKIGG, WIKI + "Factory"),
        marker("monster", "Shy Girl (gas station)", "Reported in the gas station outside the factory.", FANDOM_EN, "https://activemattergame.fandom.com/"),
    ],
    "dogorsk": [
        marker("dynamic-extraction", "Dynamic extraction points", "Extraction points on Dogorsk are dynamic (added in 0.4.0.122); positions vary.", OFFICIAL, NEWS + "324"),
        marker("building", "Cinema storage", "Opened by the Cinema storage key.", WIKIGG, WIKI + "Category:Dogorsk_keys"),
        marker("building", "Hangar 29", "Opened by the Hangar 29 key.", WIKIGG, WIKI + "Category:Dogorsk_keys"),
        marker("building", "Hangar 35", "Opened by the Hangar 35 key.", WIKIGG, WIKI + "Category:Dogorsk_keys"),
        marker("building", "Building 5", None, WIKIGG, WIKI + "Dogorsk"),
        marker("special-item", "Timeline scars (Timeline Collision)", "During Dogorsk: Timeline Collision, zones marked by wrecked WWII-era vehicles contain timeline scars holding Fracture Shards.", OFFICIAL, NEWS + "237", items=["fracture-shard"]),
    ],
    "cargo-port": [
        marker("extraction-point", "Portal on a barge near the shore", "\"There is a portal on one of the barges near the shore\" (in-game objective text).", WIKIGG, WIKI + "Cargo_Port", objectives=["harbor-anomaly-entrance"]),
        marker("landmark", "The crane", "Locked; requires the Port crane key.", OFFICIAL, NEWS + "206"),
        marker("container", "The \"Stash\"", "Known as the \"Stash\".", OFFICIAL, NEWS + "52"),
        marker("area", "Floating barges", "Barges affected by a large gravity anomaly at the center.", OFFICIAL, NEWS + "241"),
        marker("building", "Locked barns", None, OFFICIAL, NEWS + "45"),
        marker("anomaly", "Colossal gravity anomaly", "A large black gravity ball at the center of the barges.", WIKIGG, WIKI + "Dalniy_Island"),
    ],
    "dam": [
        marker("building", "Dam", "High-value gear inside the dam.", OFFICIAL, NEWS + "223"),
        marker("building", "Administration building", "High-value gear inside.", OFFICIAL, NEWS + "223"),
        marker("enemy-camp", "The workshop (Flowermen den)", "The workshop is a Flowermen den.", OFFICIAL, NEWS + "320"),
        marker("landmark", "Submarine", "A large submarine frozen in space.", WIKIGG, WIKI + "Dalniy_Island"),
        marker("extraction-point", "Final extraction point", "Appears after the zone's collapse phase.", OFFICIAL, NEWS + "320",
               metadata={"conditions": "Only after the collapse phase."}),
    ],
    "park": [
        marker("landmark", "Greenhouse", "The Park's main landmark.", OFFICIAL, NEWS + "223"),
        marker("building", "Abandoned lab (Sector Flora)", "Beneath the greenhouse. Keys include the Underground laboratory key and Lab safe key.", OFFICIAL, NEWS + "171"),
        marker("building", "The bunker", "Opened with the Bunker south entrance key.", OFFICIAL, NEWS + "223"),
        marker("building", "Contract building", "Its roof has a gravity zone.", OFFICIAL, NEWS + "223"),
    ],
    "military-base": [
        *[marker("building", name, f"Opened by the {name} key.", WIKIGG, WIKI + "Category:Military_Base_keys")
          for name in ("Armory 1", "Armory 2", "Hangar 148", "Hangar 209")],
    ],
    "scrapyard": [
        marker("extraction-point", "Central extraction portal", "The lone extraction portal in the center of the map. Flowermen reported nearby.", WIKIGG, WIKI + "Scrapyard"),
        marker("building", "Factory building", "Towards the north.", WIKIGG, WIKI + "Scrapyard"),
        marker("building", "Hangar 84", "To the southwest; opened by the Hangar 84 key.", WIKIGG, WIKI + "Scrapyard"),
    ],
    "headquarters": [
        marker("building", "Headquarters bunker", "Named in Assistance Protocol objective text.", WIKIGG, WIKI + "Headquarters:_Assistance_Protocol"),
        marker("building", "North bunker", None, WIKIGG, WIKI + "Headquarters:_Assistance_Protocol"),
        marker("building", "South bunker", None, WIKIGG, WIKI + "Headquarters:_Assistance_Protocol"),
        marker("area", "Airport area", "Includes the terminal.", WIKIGG, WIKI + "Headquarters:_Assistance_Protocol"),
    ],
    "airport": [
        marker("building", "Hangar 8A", "Interior access is key-gated after the wall hole was sealed in 0.4.0.156.", OFFICIAL, NEWS + "339"),
        marker("landmark", "Plane in a gravity anomaly", "A plane suspended in a gravity anomaly.", OFFICIAL, NEWS + "291"),
    ],
    "downtown": [
        marker("landmark", "Pier staircase", None, OFFICIAL, NEWS + "298"),
    ],
    "gigastructure": [
        marker("extraction-point", "Extraction elevator", "Extraction is reached via an open elevator playing elevator music; arrows on closed elevator doors point toward it.", WIKIGG, WIKI + "Gigastructure",
               metadata={"conditions": "Portal opens in 5 seconds."}),
        marker("landmark", "Lobby", "Survivors of an iteration reach the lobby with available extraction points and the option to re-enter.", OFFICIAL, NEWS + "193"),
        marker("container", "Storage room", "White door; needs the Storage room key.", WIKIGG, WIKI + "Gigastructure"),
    ],
}


def main() -> None:
    (DATA / "reference.json").write_text(json.dumps(reference, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    (DATA / "objectives.json").write_text(json.dumps(OBJECTIVES, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    total = 0
    for slug, markers in MARKERS.items():
        payload = {
            "format": "active-matter-map/v1",
            "map": slug,
            "version": "0.4.0.156",
            "markers": [{k: v for k, v in m.items() if v is not None} for m in markers],
        }
        total += len(markers)
        (DATA / "maps" / f"{slug}.json").write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    print(f"Wrote {len(reference['maps'])} maps, {len(OBJECTIVES['objectives'])} objectives, {total} unplaced markers.")


if __name__ == "__main__":
    main()
