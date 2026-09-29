# Map data strategy

## Update (2026-09-29)

The maintainer chose, accepting the legal risk, to use activematterhelp.ru's map imagery for 12 raids (see [data-import.md](data-import.md#map-base-images)). The original strategy below still applies to any future replacement with original art.

## Decision

**Option C: an original map representation drawn by contributors from their own gameplay, with a schematic grid until a map exists.**

The app never depends on redistributing extracted or third-party map imagery.

## Options evaluated

| Option                                                    | Verdict                   | Why                                                                                                                                                                                                                       |
| --------------------------------------------------------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A. Officially provided map assets                         | Not available             | We found no official map images, fan kit or asset licence on activematter.game. Revisit if Gaijin grants permission (contentpartners@gaijin.net)                                                                          |
| B. Community maps with compatible licences                | Rejected for base imagery | The wikis' own text is CC BY-SA, but the map images they host are screenshots of Gaijin's game. The wiki licence cannot relicense Gaijin's copyright. activematterhelp.ru and the GitHub repo have no or unknown licences |
| C. Original representation from screenshots and reference | **Chosen**                | The Content Creator Guidelines cover screenshots in free, public, non-commercial fan content that makes a "significant creative contribution". A hand-traced schematic (roads, buildings, water, labels) is original work |
| D. Asset extraction for reference only                    | Rejected                  | EULA 3.2.9 forbids datamining. Guidelines 1.1.5 and 1.1.8 forbid extracting game materials and decompiling. "Reference only" does not change that                                                                         |

## How it works in the app

1. **Coordinates are normalised.** Markers store `x` and `y` as a percentage of the map (0–100, with the origin at the top left). A base image can therefore be replaced at any resolution without moving markers.
2. **Schematic fallback.** Until a map has a base image, `GameMap.vue` draws an original lettered grid (A–J by 1–10). The grid gives players a shared vocabulary ("portal is around C4"), and the page explains that no base map has been traced yet.
3. **Base image upload.** In Admin → Maps → _map_ → Map settings, an admin uploads a PNG, JPG or WebP that they have the right to publish, with an attribution line. It is stored on the `public` disk. Width and height are read from the file.
4. **Unplaced markers.** Places confirmed by sources but without a known position have `x`/`y` = null. They are listed in the sidebar and searchable, and editors place them later in the editor's "Only unplaced" queue.

## Contributor rules for base images

- Trace from your **own** screenshots. Do not copy other sites' maps.
- Produce original artwork: simplified shapes, your own colours, your own labels. Do not publish a raw screenshot as the base layer.
- Do not use game UI, the game's own map screens, logos or extracted textures.
- Record provenance in the map's `image_attribution` (for example "Traced by <name> from gameplay, build 0.4.0.156").

## Naming

Guidelines 1.1.7 forbids using the game's title or logo to identify another product, including in domain names. The app is therefore branded **Portal Atlas** (set with `APP_NAME`). It mentions Active Matter only descriptively ("an unofficial companion for Active Matter"), and the footer carries a non-affiliation notice. **Do not register a domain containing the game's name.**
