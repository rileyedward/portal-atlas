#!/usr/bin/env python3
"""
Builds one base image per raid from activematterhelp.ru's map tiles, cropped
to exactly the calibration box used for that raid's imported markers, so the
image and the markers line up.

  tiles cache : storage/app/private/activematterhelp/tiles/     (git-ignored)
  images      : public/map-images/<slug>.webp                   (committed; served as static files)
  manifest    : database/data/activematterhelp/map-images.json  (read by GameDataSeeder)

The site's tile pyramid is a standard Leaflet one: 512 px tiles, where zoom 0
covers the site map's full world extent (512 CRS units).

Usage (project root):
    python3 database/data/tools/build_map_images.py [--max-side 4096]
Requires Pillow with WebP support.
"""

import argparse
import io
import json
import math
import time
import urllib.error
import urllib.request
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[3]
REFERENCE = ROOT / "database" / "data" / "activematterhelp" / "reference.json"
MANIFEST = ROOT / "database" / "data" / "activematterhelp" / "map-images.json"
CACHE = ROOT / "storage" / "app" / "private" / "activematterhelp" / "tiles"
OUT = ROOT / "public" / "map-images"
BASE = "https://activematterhelp.ru/map-tiles"
TILE = 512
WORLD = 512  # CRS units at zoom 0
ATTRIBUTION = "Map imagery: Active Matter Help (activematterhelp.ru) / Gaijin Entertainment"

# Site map -> tile folder, world extent (leftTop, rightBottom as [lng, lat]) and zooms that exist.
SITE_MAPS = {
    "dalniy": {"folder": "dalniy_tiles", "lt": (-2304, -2304), "rb": (2304, 2304), "zooms": range(0, 6)},
    "forsaken_africa": {"folder": "forsaken_africa_tiles", "lt": (-2048, -2048), "rb": (2048, 2048), "zooms": range(3, 6)},
    "america_abandoned": {"folder": "america_abandoned_tiles", "lt": (-1100, -600), "rb": (170, 670), "zooms": range(0, 5)},
}
BACKGROUND = (10, 14, 20)


def to_px(site, lat, lng, zoom):
    scale = WORLD * 2 ** zoom
    x = (lng - site["lt"][0]) / (site["rb"][0] - site["lt"][0]) * scale
    y = (site["rb"][1] - lat) / (site["rb"][1] - site["lt"][1]) * scale
    return x, y


def fetch_tile(folder, z, x, y):
    path = CACHE / folder / str(z) / str(x) / f"{y}.webp"
    if path.exists():
        return Image.open(path) if path.stat().st_size else None
    path.parent.mkdir(parents=True, exist_ok=True)
    url = f"{BASE}/{folder}/{z}/{x}/{y}.webp"
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (map-image-builder)"})
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = resp.read()
    except urllib.error.HTTPError as e:
        if e.code == 404:
            path.write_bytes(b"")  # remember the gap
            return None
        raise
    path.write_bytes(data)
    time.sleep(0.05)  # be gentle with their server
    return Image.open(io.BytesIO(data))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--max-side", type=int, default=4096)
    args = parser.parse_args()

    reference = json.loads(REFERENCE.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)
    manifest = []

    for entry in reference["maps"]:
        cal = entry["metadata"]["calibration"]
        site = SITE_MAPS.get(cal.get("site_map"))
        if not site:
            continue
        lat0, lat1 = cal["lat"]
        lng0, lng1 = cal["lng"]

        # Highest available zoom that keeps the longest side within max-side.
        zoom = min(site["zooms"])
        for z in site["zooms"]:
            left, top = to_px(site, lat1, lng0, z)
            right, bottom = to_px(site, lat0, lng1, z)
            if max(right - left, bottom - top) <= args.max_side:
                zoom = z

        left, top = to_px(site, lat1, lng0, zoom)
        right, bottom = to_px(site, lat0, lng1, zoom)
        width, height = round(right - left), round(bottom - top)

        tx0, ty0 = math.floor(left / TILE), math.floor(top / TILE)
        tx1, ty1 = math.floor((right - 1) / TILE), math.floor((bottom - 1) / TILE)
        canvas = Image.new("RGB", ((tx1 - tx0 + 1) * TILE, (ty1 - ty0 + 1) * TILE), BACKGROUND)
        fetched = missing = 0
        for tx in range(tx0, tx1 + 1):
            for ty in range(ty0, ty1 + 1):
                tile = fetch_tile(site["folder"], zoom, tx, ty)
                if tile is None:
                    missing += 1
                    continue
                fetched += 1
                canvas.paste(tile.convert("RGB"), ((tx - tx0) * TILE, (ty - ty0) * TILE))

        ox, oy = round(left - tx0 * TILE), round(top - ty0 * TILE)
        image = canvas.crop((ox, oy, ox + width, oy + height))
        target = OUT / f"{entry['slug']}.webp"
        image.save(target, "WEBP", quality=82, method=6)
        manifest.append({
            "slug": entry["slug"],
            "path": f"map-images/{entry['slug']}.webp",
            "width": width,
            "height": height,
            "zoom": zoom,
            "attribution": ATTRIBUTION,
        })
        print(f"{entry['slug']:14} z{zoom} {width}x{height}px tiles {fetched} (+{missing} empty) -> {target.relative_to(ROOT)} ({target.stat().st_size // 1024} KB)")

    MANIFEST.write_text(json.dumps(manifest, indent=1) + "\n", encoding="utf-8")
    print(f"manifest: {MANIFEST.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
