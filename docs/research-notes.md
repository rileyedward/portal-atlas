# Active Matter: Raw Research Notes for a Community Map and Raid Companion

**Compiled:** 2026-09-29. Research covered official Gaijin/Matter Team sources and community sources as of late September 2026.
**Method:** I bulk-downloaded every article on the official news site (`https://activematter.game/en/news/1` to `/345`, 170+ articles, Oct 2024 to Sep 26 2026) and extracted their text. I also pulled wiki content through the MediaWiki APIs of activematter.wiki.gg, activemattergame.fandom.com (EN) and active-matter.fandom.com/ru (RU), and read the Steam page, Wikipedia, the Gaijin legal pages and GitHub.

**Labels used throughout**

- **[OFF]** = official source (activematter.game, Steam page text written by the publisher, gaijinent.com, legal.gaijin.net).
- **[COM]** = community source (wikis, GitHub, fan sites).
- **Confidence: H / M / L.**
- An official news URL written as `news/NNN` means `https://activematter.game/en/news/NNN`.

**Honesty rules I followed:** every name below appears verbatim in the cited source. I have flagged anything I could not verify. I have not invented coordinates. Where a source gives a relative position ("south of X"), I quote it as that source's claim.

---

## 1. Developer, publisher, release status, current version

| Fact                                 | Value                                                                                                                                                                                                                     | Source                                                                                                                                                                                                                        | Type        | Conf              |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- | ----------------- |
| Developer                            | **Matter Team** (the site footer writes it "Matter.Team")                                                                                                                                                                 | Steam page https://store.steampowered.com/app/2887580/Active_Matter/ ; https://gaijinent.com/game/active-matter ; the official site footer lists "Gaijin Entertainment" and "Matter.Team"                                     | OFF         | H                 |
| Publisher                            | **Gaijin Entertainment**. The Steam listing names "Gaijin Network Ltd" as publisher. The official site copyright line reads "© 2026 Gaijin Games Kft."                                                                    | Steam page; gaijinent.com; activematter.game footer                                                                                                                                                                           | OFF         | H                 |
| Engine                               | Dagor Engine                                                                                                                                                                                                              | Official FAQ https://activematter.game/en/faq ("Active Matter runs on Dagor Engine.")                                                                                                                                         | OFF         | H                 |
| Announced                            | October 2024. The first news posts are dated 28 Oct 2024 (news/6, 7, 8)                                                                                                                                                   | news/6                                                                                                                                                                                                                        | OFF         | H                 |
| Early version (early access)         | Launched **9 Sep 2025** on PC through the Gaijin Store/launcher only                                                                                                                                                      | news/27 "The Early Version of Active Matter Will Launch on September 9th"; news/28 dated 09 Sep 2025; gaijinent.com says "Early PC version: September 9, 2025". One third-party search summary said Sep 8, which is **wrong** | OFF         | H                 |
| Full release (1.0)                   | **15 Sep 2026** on PC (Steam and Gaijin Store), PlayStation 5 and Xbox Series X\|S                                                                                                                                        | news/290 (25 Aug 2026); news/318 "Active Matter Is Now Available On PC, PlayStation 5, And Xbox Series X\|S!" (15 Sep 2026); Steam page                                                                                       | OFF         | H                 |
| Business model                       | Buy-to-play. Standard Edition is $29.99, with Advanced, Deluxe and Elite Squad (Gaijin Store only) editions above it                                                                                                      | news/290; FAQ                                                                                                                                                                                                                 | OFF         | H                 |
| Cross-play                           | Three options: own platform only, consoles only, or any platform. Progress is shared only between the Steam and Gaijin.Net PC versions, not between PC and console                                                        | FAQ; news/302                                                                                                                                                                                                                 | OFF         | H                 |
| Languages                            | English, German, French, Russian, Chinese, Spanish, Portuguese                                                                                                                                                            | FAQ                                                                                                                                                                                                                           | OFF         | H                 |
| Steam review status at time of fetch | "Mostly Positive", about 79% of about 1,065 reviews                                                                                                                                                                       | Steam page (WebFetch summary, 2026-09-29)                                                                                                                                                                                     | OFF (store) | M (changes daily) |
| **Latest major update**              | **"250 Shades of Liberty"**, released 27 Aug 2026                                                                                                                                                                         | news/291                                                                                                                                                                                                                      | OFF         | H                 |
| Post-launch content update           | **"The Fundamental Protocol"**, 24 Sep 2026. It adds "Cycle 0: Fundamental Protocol", a way to spend Merits (no build number is given in the post)                                                                        | news/336                                                                                                                                                                                                                      | OFF         | H                 |
| **Latest build number**              | **0.4.0.156**, 26 Sep 2026                                                                                                                                                                                                | news/339                                                                                                                                                                                                                      | OFF         | H                 |
| Version-line history                 | 0.1.0.x = early version (Sep to Nov 2025). 0.2.1.x = "Fire Walk" update (18 Dec 2025 onward). 0.3.0.x = "Gigastructure" update (1 Apr 2026 onward). 0.4.0.x = "250 Shades of Liberty" and 1.0 launch (27 Aug 2026 onward) | news index                                                                                                                                                                                                                    | OFF         | H                 |
| Wipe at release                      | None: "There won't be a wipe with the release of the game."                                                                                                                                                               | news/302                                                                                                                                                                                                                      | OFF         | H                 |
| Roadmap                              | The FAQ (updated 15.09.2026) says "A detailed roadmap ... will be published within a couple of weeks after release." It had **not been published** as of 29 Sep 2026; I checked news IDs up to 345                        | FAQ                                                                                                                                                                                                                           | OFF         | H                 |

The build number changes about every 1 to 3 days. **Do not hard-code "current version" in the app.** Scrape it or update it manually.

Official channels:

- Site: https://activematter.game/en
- X: https://x.com/amgame_official
- Discord: http://discord.gg/RWajbUQZfH
- YouTube: https://www.youtube.com/@Active_Matter
- Facebook: https://www.facebook.com/PlayActiveMatter/
- Instagram: https://www.instagram.com/active.matter.official
- TikTok: https://www.tiktok.com/@active_matter
- Support: https://support.gaijin.net
- Patch-note image CDN: `patchnotes.cdn.gaijin.net/active_matter_pc/...`

Official news posts are also mirrored as X articles (for example https://x.com/amgame_official/article/2099549594602381653 for Update 0.4.0.93).

Wikipedia (flagged "unreliable sources" as of Sep 2026) agrees with the above: developer Matter Team, publisher Gaijin, Dagor Engine, composer Akira Yamaoka. https://en.wikipedia.org/wiki/Active_Matter_(video_game)

---

## 2. Maps and locations

### 2a. Biomes and regions [OFF]

- FAQ: "At launch, there are three main biomes: Soviet laboratories on a restricted island, a quarantine zone in Central Africa, and an abandoned city on the U.S. coast." (https://activematter.game/en/faq). Conf H.
- news/290: "a Soviet closed island, a Central Africa quarantine zone and a dilapidated North American metropolis." Conf H.
- Region names used officially:
    - **Dalniy Island** (also spelled "Dalny Island" in news/104 and news/291).
    - **Anguka Anga**: the African region. The only official mention is news/315: "...on Dalniy Island and in Anguka Anga". Conf H that the name exists; its exact raid membership is not officially listed.
    - **America**, the North American city. Community sources call the city **Saltriver City** (wiki.gg "Downtown: Return Address" quotes an in-game description: "Downtown, the Saltriver City district"). Conf M.

### 2b. Raids (maps) currently in the game

Each variant of a raid is listed. Where the region is not officially stated, that is noted.

| Raid (exact name)                                                           | Region                                                                                                                                                                                                                                                                                                                                                                                 | Status (late Sep 2026)                                                                                                            | Key official source                                                 | Conf                   |
| --------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- | ---------------------- |
| **Shegolskoe** (news/170 also spells it "Shchegolskoe"; Russian Щегольское) | Dalniy Island, "southern edge"                                                                                                                                                                                                                                                                                                                                                         | Live. Most popular raid of 2025 (news/131)                                                                                        | news/10, news/223                                                   | H                      |
| Shegolskoe: Overgrowth                                                      | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Variant (wiki.gg; news/70 mentions "Overgrowth and Distortion Raid variants")                                                     | wiki.gg Shegolskoe                                                  | M                      |
| Shegolskoe: Distortion                                                      | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Variant                                                                                                                           | news/142; wiki.gg                                                   | H                      |
| **Ozernoe**                                                                 | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Live                                                                                                                              | news/13                                                             | H                      |
| Ozernoe: Overgrowth                                                         | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Variant                                                                                                                           | wiki.gg Ozernoe; wiki.gg Monolith page                              | M                      |
| Ozernoe: Firestorm                                                          | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Limited-time Overtime Raid (Jan 2026)                                                                                             | news/147                                                            | H (not currently live) |
| **Factory** (wiki.gg: "also known as Malie Chelni")                         | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Live                                                                                                                              | news/91, 170, 315                                                   | H                      |
| Factory: Hive                                                               | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Variant                                                                                                                           | wiki.gg Factory; news/120 "photo of the Hive in the 'Factory' raid" | M                      |
| Factory: Bloodbath                                                          | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Recurring Overtime Raid (Oct 2025, Feb 2026, Jul 2026)                                                                            | news/91, 165, 275                                                   | H                      |
| **Dogorsk**                                                                 | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Live. Went from isolated-only to open play in "Fire Walk"                                                                         | news/104; news/324                                                  | H                      |
| Dogorsk: Timeline Collision                                                 | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Recurring Overtime Raid (May and Aug 2026)                                                                                        | news/237, 283                                                       | H                      |
| "Escape from Dogorsk"                                                       | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Named once in a bug fix ("final portal was missing in the 'Escape from Dogorsk' Raid"). Current status unknown                    | grep of the official notes                                          | M name / L status      |
| **Cargo Port**                                                              | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Live                                                                                                                              | news/45, 206, 241                                                   | H                      |
| Cargo Port: Darkness                                                        | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Variant. "Luckiest raid" of 2025                                                                                                  | news/131, 303                                                       | H                      |
| Cargo Port: Deep Cover                                                      | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Recurring solo Overtime Raid                                                                                                      | news/85, 141, 169, 266                                              | H                      |
| **Dam**                                                                     | Dalniy Island                                                                                                                                                                                                                                                                                                                                                                          | Live since "Fire Walk" (Dec 2025)                                                                                                 | news/102, 104, 223                                                  | H                      |
| **Park**                                                                    | Dalniy Island, "between Shegolskoe and Dogorsk"                                                                                                                                                                                                                                                                                                                                        | Live since "Gigastructure" (Apr 2026)                                                                                             | news/171, 192, 223                                                  | H                      |
| **Military Base**                                                           | Dalniy Island (per wiki.gg; not stated officially)                                                                                                                                                                                                                                                                                                                                     | Live                                                                                                                              | news/14, 45, 83                                                     | H name / M region      |
| Military Base: Unstable Zone                                                | as above                                                                                                                                                                                                                                                                                                                                                                               | Variant                                                                                                                           | news/72, 327                                                        | H                      |
| Military Base: Silent Observers                                             | as above                                                                                                                                                                                                                                                                                                                                                                               | Variant. Named in a player question in news/82; no official description                                                           | news/82                                                             | M                      |
| **Scrapyard**                                                               | Dalniy Island (per wiki.gg)                                                                                                                                                                                                                                                                                                                                                            | Live, always in its "Midnight" setting (wiki.gg)                                                                                  | news/294                                                            | H name / M details     |
| Scrapyard: Midnight                                                         | as above                                                                                                                                                                                                                                                                                                                                                                               | Variant name                                                                                                                      | news/137                                                            | H                      |
| **Headquarters**                                                            | **Not explicitly stated.** The official tour (news/223) groups it with Dalniy Island zones and describes "military bases, command centers, civilian blocks, and an airfield ... In this sweltering city". The wiki.gg navbox lists it under "Other", not Dalniy Island, and the GitHub map repo groups "Штаб/Африка" (HQ/Africa). **Unverified whether HQ is in Anguka Anga (Africa)** | Live                                                                                                                              | news/223                                                            | H name / L region      |
| Headquarters: Unstable Zone                                                 | ?                                                                                                                                                                                                                                                                                                                                                                                      | Variant                                                                                                                           | news/54, 72, 77                                                     | H                      |
| Headquarters: Code 2901                                                     | ?                                                                                                                                                                                                                                                                                                                                                                                      | Recurring solo Overtime Raid (Mothman transformation)                                                                             | news/97, 174                                                        | H                      |
| Headquarters: Assistance Protocol                                           | ?                                                                                                                                                                                                                                                                                                                                                                                      | Overtime Raid, Jun 2026. A testbed for a planned whole-island raid                                                                | news/256, 263, 270                                                  | H                      |
| **Airport**                                                                 | **Africa** ("Added a new raid set in Africa: Airport")                                                                                                                                                                                                                                                                                                                                 | Live since 27 Aug 2026                                                                                                            | news/291                                                            | H                      |
| **Downtown**                                                                | America (North American city)                                                                                                                                                                                                                                                                                                                                                          | Live since 27 Aug 2026. Duration raised from 30 to 45 min                                                                         | news/291, 303                                                       | H                      |
| Downtown: Unstable Zone                                                     | America                                                                                                                                                                                                                                                                                                                                                                                | Live                                                                                                                              | news/291                                                            | H                      |
| Downtown: Collapse                                                          | America                                                                                                                                                                                                                                                                                                                                                                                | Updated version of the former Overtime Raid "America: Collapse"                                                                   | news/291; news/81                                                   | H                      |
| **Gigastructure**                                                           | "Soviet-era building stranded between worlds". Open (PvPvE) raids only                                                                                                                                                                                                                                                                                                                 | Live. Added 1 Apr 2026 with access initially locked; unlocked through the "Gigastructure: Entry Protocol" investigation (wiki.gg) | news/184, 193, 192, 302                                             | H                      |
| **Singularity Point**                                                       | Tutorial / isolated first raid                                                                                                                                                                                                                                                                                                                                                         | Live (wiki.gg)                                                                                                                    | wiki.gg only; **no official news mention found**                    | M                      |
| Epicenter                                                                   | Unreleased. Devs, 7 Sep 2026: "The Agency's top scientists still haven't been able to stabilize the epicenter"                                                                                                                                                                                                                                                                         | Not in game                                                                                                                       | news/302, 155                                                       | H (unreleased)         |
| "Lone Wolf"                                                                 | Not a map. A solo-only Overtime format rotating daily between Shchegolskoe, Ozernoe and Factory (Feb 2026)                                                                                                                                                                                                                                                                             | news/170                                                                                                                          | H                                                                   |
| Euclid                                                                      | wiki.gg navbox lists "Euclid" as a _removed_ raid. In current wiki usage, "Euclid" is also the name of the earth-mound gravity anomalies                                                                                                                                                                                                                                               | wiki.gg                                                                                                                           | L                                                                   |

**Nexus Battles (separate PvP mode, not extraction maps).** The only map named officially is **Vanilla Mall** (news/192). news/28 describes Nexus maps as "from Aztec temples to futuristic skyscrapers" without naming them. Several SEO sites list "Nexus" as a "raid map"; that is misleading.

**Raid modes (terminology).**

- **Open Raids**: PvPvE.
- **Isolated Raids**: PvE, with no other players' squads. Introduced as "Isolated PvE raids" in news/31 and split into "Open and Isolated" in news/192.
- **Overtime Raids**: limited-time events.
- **Unstable Zone**: PvP-focused with a shrinking zone. "won't include making Unstable Zone modes playable in Isolated Raids" (news/302).
- Open Raids rotate maps; "All existing Isolated Raids, including their variants, can be played anytime" (news/302).
- A "Rotation" variant for Isolated raids replaced "Random" in 0.4.0.156 (news/339).
- Players per raid: "anywhere between 4 and 20" (news/41, Sep 2025).
- Squad size: up to 3 (news/28).

### 2c. Map geography: relative positions stated by sources

These are the only positional statements I found. All are **approximate and textual**. No source gives official coordinates.

- [OFF] Shegolskoe is "on the southern edge of Dalniy Island" (news/223).
- [OFF] Park "lies between Shegolskoe and Dogorsk" (news/171).
- [COM, wiki.gg "Dalniy Island", M] The page gives a full island layout:
    - Airport in the north. (Note: this is a Dalniy Island airport in lore; the _Airport raid_ is officially in Africa. Treat these as possibly distinct.)
    - Cargo Port in the northeast, with "barges affected by ... a large black gravity ball in the center".
    - Dam south of the port, "with a large submarine frozen in space".
    - Military Base south of the dam, and Scrapyard south of that.
    - Dogorsk and Ozernoe in the southeast, "alongside a park".
    - Shegolskoe "west of the park".
    - Factory complex in the "south east corner ... in the area of Malie Chelni".
    - Source: https://activematter.wiki.gg/wiki/Dalniy_Island
- [COM, wiki.gg "Ozernoe"] "Ozernoe is located northeast of the Dogorsk raid."
- [OFF] Devs confirm the island is bigger than the playable areas. A player-made "datamined satellite composite of Dalniy Island" exists, and a "raid that covers the entire island" is in development (news/270).

---

## 3. Per-map details (POIs, extraction, enemies, anomalies, hazards)

**Extraction mechanics apply to every map; see section 4.** Named extraction points are **not given in official sources**. Official text only says "extraction portal(s)", "final portal", "final extraction point", and (Dogorsk) "dynamic extraction points". Community wikis describe portal _locations_ in words, but they do not give names.

### Shegolskoe

- **[OFF] POIs:**
    - water tower ("excellent view")
    - houses
    - front gardens
    - abandoned vehicles
    - "small apartment complexes" on the outskirts
    - floating/suspended houses ("Gravitational anomalies have ripped part of the settlement from the ground")
    - "the flying house"
    - "floating houses that were only accessible via portals in other buildings"

    Sources: news/10, 223, and patch notes.

- **[OFF] Extraction:** a player Q&A (news/82) mentions "the big extract on the floating land in the sky". The devs did not dispute it. Conf M-H.
- **[OFF] Hazards:** gravitational distortion; "listen for rustling sounds, be watchful for what's above".
- **[COM wiki.gg] POIs and extraction:**
    - Details:
        - Village of **16 numbered houses**.
        - Houses **#3 and #6 are suspended by gravity anomalies**, reached by **portals in houses #9 and #8** respectively.
        - Three-story apartment block to the north.
        - Lake and northern bridge to the west, with a downed helicopter.
        - Garages on the north and west edges.
        - Wooded area and radio tower to the southwest.
        - Burnt patches and destroyed tanks.
        - A surveillance room in house #7 and one in the three-story building.
        - Attics of houses #4, #8, #11 and #15, each opened by a key.
    - Extraction: "the lone static extraction portal at the top of a southern gravity anomaly known as a 'Euclid'. Occasionally, other extraction portals will appear."
    - "only three abnormal signals, each in the same location every raid."
    - Stash locations: "Inside the trailer next to the southern radio tower"; "On the balcony of the top apartment on the eastern portion of the three-story building."
    - Source: https://activematter.wiki.gg/wiki/Shegolskoe. Conf M.
- **[COM RU Fandom interactive map]** "Портал выхода" (exit portal): "Основной портал для выхода из рейда. Находится на вершине гравитационной аномалии. Работает пока не закончится таймер рейда." ("Main portal for leaving the raid. It sits on top of the gravity anomaly and works until the raid timer runs out.") Also a two-way portal into floating house #3. Map bounds are 1770x1770 px. Source: https://active-matter.fandom.com/ru/wiki/Карта:Щегольское. Conf M.
- **Enemies:**
    - Regular setting: ball lightning in the interior ([COM] wiki.gg).
    - Overgrowth: Flowermen and Dendroids ([COM] wiki.gg).
    - Distortion: Distorted, Mimics, Hellhounds and the Alpha Mimic ([COM] wiki.gg).
    - Car Mimic appears in "Shegolskoe Distortion" per EN Fandom.
- **Primary objective [COM wiki.gg]:** "Active Matter Collection" (collect 15 AM).

### Ozernoe

- **[OFF]** (news/13):
    - "village of Ozernoe"
    - "the sylvan glade"
    - "The observation deck, power plant, and village church are located by the lakeside"
    - sniper positions in these areas
    - "fire station" (loot, high competition)
    - "Other civilian buildings"
- Patch-note mentions: the church (news/324, Swarms), and near the helicopter (Firestorm loot).
- **[COM wiki.gg]:**
    - POIs:
        - Lake Tikhoe
        - bus station
        - fire station (including a water tower and garage)
        - post office
        - shop
        - administration building, with an extraction portal adjacent: "room adjacent to the extraction portal"
        - power substation / power station (with a "medical cache")
        - 11 houses and a church
        - "The church can be abseiled through the use of a gravity anomaly on the western wall"
    - Spawns: "northern portion ... by the bus station or power station and lake, or south of village".
    - "seven abnormal signals".
    - Turned soldiers guard "the fire station, southern road and the village".
    - Stash: "Inside a substation structure on the northeastern corner of the fire station compound."
    - Source: https://activematter.wiki.gg/wiki/Ozernoe. Conf M.
- **Enemies:**
    - [OFF]: Swarms (church); burning Devourers, Scorches and fire traps in the Firestorm variant (news/147).
    - [COM]: Turned soldiers and Distorted (church, bus terminal, lake shore); Overgrowth adds Flowermen, Dendroids and an Alpha Flowerman.

### Factory (Malie Chelni)

- **[OFF]:**
    - Blood-covered variant "Factory: Bloodbath" with data cards (news/91).
    - "construction site" with gravity anomalies (patch notes).
    - Hive: a "photo of the Hive in the Factory raid" objective.
    - Ball Lightnings (vehicle placement fix).
    - Valuable-loot descriptions were added in-game (news/315).
- **[COM wiki.gg]:**
    - "six main factory workshops, a gas station, and a northwestern extraction portal in a construction site."
    - Malie Chelni, south of the factory complex, contains "a dormitory, a park, a Euclid anomaly with the southern extraction portal and a set of garages."
    - "To the east of the dormitory is a power station complex with hangars #17 and #18 to its south."
    - A road bisects the map north/south.
    - "Two small fishing installations ... along the southern coast."
    - "six abnormal signals".
    - Enemies listed: Devourers, Flowermen, Distorted, Invisibles, Turned Soldiers, ball lightning.
    - Named buildings: Factory #1 through #5, Dormitory, Hangar #17, Hangar #18, power station chimney hive, and the ship "Severniy Veter" on the roof of Factory #3.
    - Source: https://activematter.wiki.gg/wiki/Factory. Conf M.
    - Bloodbath variant (wiki.gg): "An extraction portal has been added to the power station in the eastern compound"; "Extracting from the raid requires at minimum three data cards"; Tentacles in "the large blood lake in the center". Conf M.
- **[COM EN Fandom] Shy Girl:** "in the gas station outside the factory in the Factory map." Conf L-M.

### Dogorsk

- **[OFF]:**
    - "dynamic extraction points" added in 0.4.0.122 (news/324).
    - Alpha Mimic spawns (news/192).
    - Dendroid spawns (news/336).
    - Timeline Collision variant: "special zones marked by wrecked World War II–era vehicles", "timeline scars" containing **Fracture Shards** (news/237).
- **[COM]:**
    - "Cinema storage", "Hangar 29", "Hangar 35" (all from key names, wiki.gg Category:Dogorsk keys).
    - "Building 5" (wiki.gg gallery caption).
    - Car Mimic "in a random car" (EN Fandom).
- An RU Fandom interactive map exists: https://active-matter.fandom.com/ru/wiki/Карта:Догорск

### Cargo Port

- **[OFF]:**
    - "locked barns and flying ships" (news/45).
    - "the crane", which is locked and needs a key (news/88, 206).
    - "the 'Stash'" (news/52).
    - "the floating barges" (news/241).
    - "static barges around an active matter clump" (player question, news/270).
    - Spawns: "everyone spawns at the bottom of the map" (player claim, news/70).
- **[COM wiki.gg]:**
    - "A colossal gravitation anomaly ... There is a portal on one of the barges near the shore" (quoted in-game objective text, "Harbor Anomaly: Entrance").
    - Keys: Port crane key, Big military crate key, Small military crate key.
- **[COM EN Fandom]:** Shy Girl in an "industrial building at the north-east exit of the Cargo Port map". Conf L.
- RU Fandom map: https://active-matter.fandom.com/ru/wiki/Карта:Порт

### Dam

- **[OFF]:**
    - "Inside the dam and its administration building ... high-value gear" (news/223).
    - "the workshop" is a Flowermen den (news/320).
    - "the submarine on the Dam map" (player question, news/137).
    - Shy Girl is on the Dam anomalies list (news/142).
    - Final extraction point appears after the zone's collapse phase (news/320).
- **[COM]:** Keys: Captain's key, East storage key, West storage key, Safe key, Lock actuator board.
- RU Fandom map: https://active-matter.fandom.com/ru/wiki/Карта:Дамба

### Park

- **[OFF]:**
    - "greenest spot on Dalniy Island, overrun with Flowermen and Dendroids"
    - "Its main landmark is the greenhouse"
    - "Beneath the greenhouse, an abandoned lab"
    - "the bunker"
    - "a contract building" roof with a gravity zone

    Sources: news/171, 223, and patch notes.

- The Alpha Flowerman boss was introduced with the Park update (news/192).
- **[COM wiki.gg]:**
    - Underground lab called **Sector Flora**.
    - Keys: AM vault key, Armory key, Bunker south entrance key, Lab safe key, Underground laboratory key.
    - Park lab reagents (six named).
    - The in-game item "Map of the facility (Greenhouse bunker)" exists as a wiki page.

### Military Base

- **[OFF]:**
    - "impressive stockpiles of rare firearms" (news/14).
    - wooden crates (destructible).
    - Unstable Zone variant with "final zone-contraction locations" (news/327).
- **[COM wiki.gg]:** Keys: Armory 1 key, Armory 2 key, Hangar 148 key, Hangar 209 key.
- **[COM EN Fandom]:** "hangers being unlocked on Military base" in Unstable Zone. Corpse swarms are prominent here. Conf L.

### Scrapyard

- **[OFF]:** Variant "Scrapyard: Midnight" (news/137). Nothing else official.
- **[COM wiki.gg]:**
    - "vehicle graveyard and industrial dump site".
    - "only playable at night with extreme fog, and is filled with Distorted".
    - "Agents spawn around its exterior and fight inwards to the lone extraction portal in the center of the map."
    - "three abnormal signals ... surrounded by gravity traps".
    - roaming ball lightning.
    - Invisibles; Flowermen near the extraction portal.
    - "Towards the north is a factory building ... Hangar 84 to the southwest" (opened by the Hangar 84 key).
    - "fog ... is dissipated with the use of a thermal scope."
    - Conf M.

### Headquarters

- **[OFF]:**
    - "Military bases, command centers, civilian blocks, and an airfield ... this sweltering city ... Turned Soldiers and monsters" (news/223).
    - civilian pickup trucks with cargo trunks.
    - "Military facilities ... now always have weapons".
    - Hellhound spawns (news/296).
    - Dendroids (news/336).
    - "Desert colors" camo advice (news/166).
- **[COM wiki.gg, Assistance Protocol page]:** Named areas: "south bunker, north bunker, Headquarters bunker and the entire airport area". Objective text also mentions "the Headquarters bunker ... the terminal".

### Airport (Africa)

- **[OFF]:**
    - "search hangars with military gear and a plane suspended in a gravity anomaly" (news/291).
    - "Hangar 8A" (a wall hole was sealed and interior access is now key-gated; news/339).
    - Vehicle Transfer spawn points.
    - Valuable-loot descriptions were added in-game (news/315).
- Community detail is sparse. There is no wiki.gg Airport page, only "Raid Explorer: Airport".

### Downtown (America / Saltriver City)

- **[OFF]:**
    - "avenues, ruined skyscrapers, school buses, and taxis" (news/291).
    - "the pier's staircase" (news/298).
    - non-playable floors and rooftop blocked by barriers.
    - "public contract containers".
    - Enemies: **Police and SWAT** patrols, plus the American Flowermen variant with its own Alpha.
    - Duration 45 min.

### Gigastructure

- **[OFF]** (news/193, the "Preliminary Report"):
    - cyclical "iterations".
    - "purge" at the end of each iteration.
    - **elevators** are the only safe points ("Occupying an elevator is the only confirmed way to survive a purge phase").
    - survivors go to a "lobby" with "Available extraction points; The option to re-enter the zone".
    - risk escalates with each re-entry.
    - "Total loss of all collected loot upon death."
    - Patch notes: floor numbers; loot behind every locked door; "elevator portals".
- **[COM wiki.gg]:**
    - Extraction is reached "via an open elevator playing elevator music". Arrows on closed elevator doors point toward it.
    - Extraction portal opens in 5 s (versus 20 s in other raids).
    - Containers: "Jewel Box", "Nightstand", Storage room (white door, needs the Storage room key).
    - Loot: Fragments, Big fragments, and the Core (one per raid, Epic).
    - Enemies and hazards: Mimics, gravity traps, Invisibles, Distorted near yellow weapon crates, non-explosive Fireballs, occasional Turned Soldiers.

### Singularity Point (tutorial) [COM wiki.gg only]

Linear isolated tutorial. The player finds the "Key-Memory Card" and exits through an extraction portal.

---

## 4. Core mechanics (with sources)

### Extraction [OFF unless noted]

- Extraction happens through **extraction portals**.
- The anomalous zone **shrinks/collapses in phases**. There are on-screen warnings at 5 min and 1 min before collapse begins (news/254). A "final extraction point" appears after the collapse phase (news/320).
- In Unstable Zone raids, "the portal now appears earlier (before the final collapse) if there is only one player alive and there are no active beacons" (news/135). "The extraction portal remains locked until the second-to-last collapse phase completes" (news/72).
- Dogorsk has "dynamic extraction points" (news/324).
- Compass shows distances to extraction portals (news/144).
- Players who kill others in the Secure Zone ("Violators"/"Excommunicados") are restricted to a single extraction portal (news/261).
- Vehicles: quad-bike and pickup cargo trunks transfer their contents to the Shelter at an extraction point (news/186, 192). The Robot Dog can extract on its own (news/291).
- The in-raid map marks objectives, and the early-extraction and extraction-requirement tips (news/306).
- [COM wiki.gg] Portals take 20 s to open (5 s in Gigastructure).

### Death and retention [OFF]

- Loot outside a safe container is lost.
- **Safe Container** "automatically teleports collected items to the shelter in case of death" (news/14). You can choose not to activate it (news/276).
- **Beacon** (respawn) and **Tactical beacon** / "Enhanced Respawn Beacon" (respawn with rental gear) (news/276, 291).
- **Mindvault**: allies can revive you; hacking a mindvault yields active matter and dog tags.
- "Operative loot behavior: all gear and weapons — except the weapon in hand — now remain on the body" (news/192).

### Loot, resources, rarity

- **Active Matter (AM)**: harvested "by killing AM-transformed creatures, taken away from other players or picked up directly from Active Matter clusters" ([OFF] https://activematter.game/en/game). [COM wiki.gg] It takes no space. The in-raid cap is 300. Refining gives 100 credits.
- **Enriched items**: extracting one unlocks Replicator blueprints and gives more chronotraces/credits when refined (news/60, 77). [COM] There is also a "**Saturated**" tier of higher-value civil items (wiki.gg category, 11 items; the official 0.4.0.93 notes also refer to refining for rewards).
- **Rarity tiers confirmed officially:**
    - uncommon (green) (news/142)
    - rare (blue) (news/315)
    - Epic (news/54, 315)
    - Common appears in the wiki. [COM wiki.gg] Rarity categories are Common, Uncommon, Rare, Epic.
    - The wiki also uses "Special" and "Rusty" as item categories.
    - Jewelry names encode rarity: "cheap", "gilded", "golden" (news/300).
- **Item tags (official):** "Weapon", "Civil item", "Electronics", "Vinyl record", "Cassette tape" "and more" (news/192).
- **Rare-loot zones** are marked on the raid-selection map and in-raid with color-coded resource icons (news/291). There are also "eye icon markers for valuable loot ... visible to all players".
- **Monster clusters** are "high danger with higher-tier and more loot" (news/291).
- **Airdrops / supply drops** exist (news/31, 192, 291).
- **Chronotraces**: six types, "metal, composite, fiber, chemicals, energy, idea". They come from dismantling items (news/291).
- **Fracture Shards** appear in the Dogorsk: Timeline Collision event (news/237).
- **Remains** of monsters are fused into **Chronogenes** (perks). Tier I/II/III chronogenes exist, and Tier III has charges (news/291).

### Crafting and base [OFF]

- **Shelter** is the base, "located outside ordinary reality" (FAQ). It supports customization and decoration with raid items (news/192), a shooting range, a trophy hall (news/191), and a music player.
- **Replicator**: replication plus fusion. Fusion moved here from the Refiner in 0.4.0.x. There is a replication limit that is recharged with chronotraces (news/291).
- **Refiner**: refines and recycles items into credits and chronotraces, and repairs equipment (news/315).
- **Workshop** is a UI tab (news/339).
- **Monolith**: progression through access levels Alpha to Mu ([COM wiki.gg] 12 levels). "Personal Timelines" / self-sacrifice act as prestige. **Harmonization Cycles** are global/seasonal (Cycle 1, Cycle 2, and "Cycle 0: Fundamental Protocol").
- **Body Sleeves**: characters/classes such as Rebel, Beast, Pulse, Phantom, and Naomi Carter's sleeves.

### Objectives [OFF]

- Primary objectives, Operative objectives, **Investigations** (multi-stage quest lines, with "Cases" added in news/104), Daily, "Priority Raid" (a source of Merits), "Collaborative Objectives" ("TO ALL AGENTS"), and Additional (story) objectives.
- Contract types include:
    - Combat Practice
    - Vehicle Transfer
    - Collect and Extract
    - Specimen Capture / Anomaly Capture
    - sensor-placement
    - item-planting
    - High-Priority Cargo
- **Keys** unlock locked doors and containers. "Keys with a note" unlock secret stashes.

### Currencies [OFF]

- **Credits**.
- **Prime**: the premium currency granted by editions, which the FAQ equates with "crystallised Active Matter (Prime)".
- **Crystallised Active Matter (CAM)**: also earned from special daily objectives; not buyable with money (news/46).
- **Monolith Tokens**.
- **Merits**: earned during Harmonization Cycles (news/316, 323).
- Chronotraces also act as a crafting currency.

### Enemies and monsters (names confirmed in official text)

- **Operatives**: other players.
- **Turned Soldiers**: news/140 describes them.
- **Flowermen**: news/156 describes them.
- **Alpha Flowerman**: boss (news/185, 192). African and American variants exist (news/291).
- **Dendroids**.
- **Distorted**: includes a "tougher variant capable of summoning kin with screams" (news/324 summary).
- **Distorted Hunter** / "Hunter-Distorted": a player-transformable form (news/188).
- **Mimic**, **Alpha Mimic** (explodes on death), and **Mimic Car** / "Car Mimic".
- **Invisible(s)**.
- **Hellhound(s)**.
- **Shy Girl**.
- **Scorch(es)**.
- **Devourer(s)** (including burning Devourers).
- **Swarm(s)** and Swarm **hives**.
- **Statues**, with "stone cairns" per the EN Fandom in-game note quote.
- **Poltergeist**.
- **Seeds**.
- **Mothman**: a player transformation in HQ: Code 2901.
- **Police / SWAT**: in Downtown.
- "**Burned Out Operatives**": NPC bodies in Isolated Raids (news/77 etc.).
- WWII-era altered soldiers: in Timeline Collision.

### Anomalies (official names)

- gravitational distortion / gravity zones / "gravity anomalies"
- **gravity traps**
- **Ball Lightning**
- **Fireball**
- **Fire** anomaly
- **Tentacle** anomaly
- **Anomalous Flowers** / lure-bushes / roots
- fire traps

Sources: news/7, 28, 104, 192, 291, 261. The "Warning! Danger!" in-game notes describe anomalies (news/155).

---

## 5. Named items (explicitly documented)

### Official [OFF]: edition contents (news/223 page modal; also news/290)

- **Weapons:**
    - SOK-94 Vepr Carbine
    - MB590 shotgun
    - MP5
    - M9 pistol
    - Combat knife
    - SV-98
    - M4A1
    - UMP45
    - PKM
    - M110A1
    - SCAR-L
    - SPAS-12
    - Hunting crossbow
    - Scorpion EVO 3
    - MP-443
    - War hammer
- **Mods:**
    - Scope PSO AK 4.0x
    - 1P69 Scope 1-10x
    - EXPS3 Collimator
    - Thermal Scope S350F 2.0x
    - TNG6 Scope 1-6x
    - TA31 Scope 4.0x
    - Moosemark Collimator
    - HX-QD Suppressor for M110A1
    - BN45 Suppressor
    - Mini 556 Suppressor
- **Equipment:**
    - Small / Medium safe container
    - Beacon (respawn)
    - Tactical beacon
    - Small / Medium / Large Backpack
    - Small Pouch, Medium Pouches
    - Small / Medium Magazine Pouches
    - Tactical PASGT Helmet, A3 Helmet, SF Helmet
    - Steel / Ceramic / Titan armor plate (S/L). XL size also exists (news/104).
    - Flashlight
    - First aid kit, Big first aid kit
    - Painkiller
    - F-1 grenade
    - Scout drone "Harpy"
    - Assault quad bike "Bonecrusher"
    - Robot Dog "胡安一号"
- **Ammo:** 7.62x39mm, 12 Gauge, 9x19mm, 7.62x51mm, 5.56mm and .45 ACP ammo boxes, plus weapon magazines.

### Official [OFF]: added in updates

- **Weapons:**
    - "Fire Walk": M249 Para, Origin-12, RPG-7, SVDM, AEK-971, AN-94, PP-19-01, M9 Tactical.
    - "Gigastructure": M24, Stechkin, PP-2000, Gepard, VSSK Vykhlop, EV-MG, and the **Gravity gun** (raid-only).
    - "250 Shades of Liberty": RSh-12, MAC-11, P17-V, SCAR-H, M14, M39 EMR, DP-12, plus melee weapons (Crowbar, Pry bar, Baseball bat, Police Flashlight).
    - Timeline Collision: PPSh-41, TT pistol, M1928A1, MP-40, Stahlhelm, M1 helmet, ushanka.
- **Helmets and headsets:** EDH-Gen V, TFMK-I, Tank crew helmet, PNV-57E NVD, Tactical headset.
- **Laser sights:** BH-LGR03, OM, Blue Star, DBAL-A2, AN-PEQ-15, Dlan-3, 4TK, C5.
- **Other gear and items:**
    - Kamikaze drone, Thermal drone
    - M8 Smoke grenade, RGD-M Smoke grenade
    - Streaming Injector, Burst Injector, "Medications"
    - fire extinguisher
    - CF-400 Flamethrower
    - sleds
- **Named resources:** Active Matter; Crystallised Active Matter; Fracture Shards; the six chronotrace types; data cards (Bloodbath); "Monolith Emission Scanner" (quest); "Lock actuator board" (a key); "Keys with a note"; radio parts (uncommon/green); gold jewelry (rings, bracelets, earrings in cheap, gilded and golden tiers).

### Community [COM wiki.gg, CC BY-SA 4.0], conf M

- wiki.gg has **784 items tagged with a rarity**: Common 405, Uncommon 196, Rare 184, Epic 3.
- The full category lists with rarity tags are in the **Appendix**.
- Examples:
    - Active Matter (item): Uncommon, refines for 100 credits.
    - Credit (currency).
    - Chronotrace: Uncommon, but Idea is Rare.
    - Fragment: Uncommon.
    - Core: Epic.
    - Beacon: Rare, costs 50 Prime.
    - Dog tag: Epic.
    - Robot Dog signal grenade: Epic.

---

## 6. Existing competitor tools

| Tool                                          | URL                                                                                                                                                                                            | Coverage                                                                                                                                                                                                                                                                                            | Quality / notes                                                                                                                                                                                                                                                                   | Type |
| --------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---- |
| **Active Matter Wiki (wiki.gg)**              | https://activematter.wiki.gg/                                                                                                                                                                  | 954 articles, 1,184 images. Items, keys, notes, investigations, objectives, and raid pages for Factory, Gigastructure, Ozernoe, Scrapyard, Shegolskoe and Singularity Point. **No pages for Cargo Port, Dam, Dogorsk, Park, Military Base, Headquarters, Downtown or Airport.** No interactive maps | Best English source. Highly detailed, but only 2 active users and 1 admin. Some copy-paste errors: the "Cargo Port: Deep Cover" overview contains Dogorsk text. License **CC BY-SA 4.0**. MediaWiki API is open (api.php works; `list=search` returned 403 to my scripted client) | COM  |
| **Active Matter Wiki (Fandom, EN)**           | https://activemattergame.fandom.com/                                                                                                                                                           | 123 articles, 1 active user. Weapons plus about 15 monster pages. **No interactive maps** (Map namespace empty). Raid pages are stubs or vandalized (e.g. "In the Dam raid you raid the dam!")                                                                                                      | Low. Monster pages contain useful in-game note quotes. CC BY-SA                                                                                                                                                                                                                   | COM  |
| **Active Matter Вики (Fandom, RU)**           | https://active-matter.fandom.com/ru/                                                                                                                                                           | 45 articles. **5 interactive maps**: Карта:Дамба, Карта:Догорск, Карта:Озёрное, Карта:Порт, Карта:Щегольское. They are small, e.g. Shegolskoe has 11 markers (portals, anomaly spots, navigation)                                                                                                   | Low-medium. Uses Fandom's built-in map JSON. CC BY-SA                                                                                                                                                                                                                             | COM  |
| **Active Matter Help**                        | https://activematterhelp.ru/ (RU/EN/ZH)                                                                                                                                                        | React SPA: interactive maps "with loot, points and monsters", weapon TTK tier lists, melee TTK, armor/damage calculators, item "where to find" database. Guides, monsters and lore marked "Скоро" (coming soon)                                                                                     | **Probably the strongest competitor.** Per the GitHub repo that imported it: 4 regions (Dalniy Island, HQ/Africa, Abandoned America, Bunker), about 5,600 POIs, 380 zones. Fan project, "Не связан с разработчиками" (not affiliated). **License unknown; do not copy its data**  | COM  |
| **zolotayaikona/active-matter-maps** (GitHub) | https://github.com/zolotayaikona/active-matter-maps                                                                                                                                            | Discord Activity plus web map viewer. Imports RU Fandom maps and activematterhelp.ru data (about 5,600 POIs). Category toggles: exits, spawns, doors and keys, valuables, loot, documents, enemies, hazards, quests, puzzles                                                                        | Created **2026-09-29** (today), 0 stars, **no license**. Redistributes third-party data of unclear license. Useful as a signal of the data model others use                                                                                                                       | COM  |
| Map Genie                                     | https://mapgenie.io/active-matter returns **404**                                                                                                                                              | none                                                                                                                                                                                                                                                                                                | No Map Genie coverage as of 2026-09-29                                                                                                                                                                                                                                            | n/a  |
| SEO "wiki" sites                              | activematter-game.wiki, activematterwiki.online, active-matter.wiki, activematterwiki.wiki, activemattergame.wiki, activematter.work, activematterwiki.space, active-matter.info, ninewiki.com | Text guides with "maps" hubs. **No real interactive maps found** (WebFetch of /maps pages showed only link hubs). All say they are unaffiliated                                                                                                                                                     | **Low. Treat as unreliable.** A search summary drawn from them claimed "15 distinct locations" and listed Nexus as a raid map, which is misleading. Do not source data from them                                                                                                  | COM  |
| Guide articles                                | getrankd.gg, gametruth.com, boostroom.com, overgear.com, allthings.how, games.gg                                                                                                               | Beginner and extraction guides                                                                                                                                                                                                                                                                      | Low-medium; not map data                                                                                                                                                                                                                                                          | COM  |
| Reddit                                        | reddit.com/r/ActiveMatter                                                                                                                                                                      | Could not verify: Reddit returned 403 to my requests                                                                                                                                                                                                                                                | Unverified                                                                                                                                                                                                                                                                        | COM  |

---

## 7. Legal: Gaijin Terms of Service, EULA, and Guidelines for Content Creators

All quotes below are verbatim, from pages fetched on 2026-09-29.

### Gaijin Terms of Service (includes the EULA as Annex #2). https://legal.gaijin.net/termsofservice

The page says "Current version" and links archived versions ("from June 2, 2025"). `https://legal.gaijin.net/eula` served the same document.

- **4.1:** "All rights to the Service(s) and associated intellectual property ... are held by Gaijin ... Gaijin grants Users a non-exclusive, non-transferable, non-sublicensable, limited license to use the Service(s) solely for personal, non-commercial purposes ... this license does not permit the use of the Service(s), Game(s), or any related content, data, or materials for AI training, machine learning, **database creation**, model development, or similar purposes, unless expressly authorized by Gaijin in writing."
- **4.3.2:** users shall not "copy, distribute, resell, rent, lease, reproduce, modify, adapt, sublicense, publicly display, reverse engineer, decompile, translate, disassemble, delete technical protection measures, or create derivative works;"
- **4.3.5:** users shall not "use, access, copy, scrape, extract, collect ... images, text ... for training, developing ... any artificial intelligence ... dataset, or tool, unless expressly authorized by Gaijin in writing."
- **4.8:** "Gaijin allows Users to create content using Gaijin Game(s) in accordance with the Terms of Service, the EULA, and the specific guidelines outlined in Gaijin's Guidelines For Content Creators. User is solely responsible for any UGC User makes public. When sharing UGC, User must clearly indicate that it is their own creation and ensure it is not presented as content that has been approved, endorsed, or created in collaboration with, or at the request of, Gaijin or its affiliates. Gaijin reserves the right, at its sole discretion, to request the removal of any UGC at any time."
- **EULA 3.2.2:** "modify, translate, create derivative works of, reverse engineer, disassemble, or decompile the Game(s) (in whole or in part)."
- **EULA 3.2.5 No Commercial Use:** "use the Game(s) or any part of it for commercial purposes, including but not limited to (1) engaging with or facilitating commercial advertising or offers ..."
- **EULA 3.2.8 No Unauthorized Connections:** "...using software, programs, or specialized tools not permitted by Gaijin."
- **EULA 3.2.9 No Data Mining:** "mine or collect any data from the Game(s) that is not intended to be publicly displayed through normal gameplay, as intended by Gaijin."
- **EULA 4.1:** "the computer code, visual images, sounds, systems, methods of operation, documentation, and other content within the Game(s) are the intellectual property and/or valuable trade secrets of Gaijin..."

### Gaijin Guidelines for Content Creators. https://legal.gaijin.net/contentrules (Last updated: June 02, 2025)

- **Scope:** "...CREATING CONTENT THAT USES GRAPHICAL, AUDIOVISUAL, AND OTHER GAME(S) MATERIALS, INCLUDING LOGOS, GAMEPLAY FOOTAGE, AND **SCREENSHOTS** ('GAIJIN MATERIALS'). CONTENT CAN INCLUDE, BUT IS NOT LIMITED TO, VIDEO REVIEWS, GAMEPLAY STREAMING, TEXT REVIEWS, AND NOTES ('CONTENT'). CONTENT MUST BE DISTRIBUTED ONLINE VIA STREAMING OR PUBLISHED ON INTERNET PLATFORMS ('PLATFORMS') AND **MADE FREELY AVAILABLE TO THE PUBLIC**. GAIJIN RESERVES THE RIGHT TO REMOVE OR REPORT ANY CONTENT..."
- **1.1.1 Gaijin's Official Content:** "The Creator will not assert or give the impression that the Content is produced or approved by Gaijin..."
- **1.1.2 Limited Commercial Use:** "The Creator will not use the Content, Gaijin Materials, or the Game(s) for commercial gain or any other financial benefit unless specifically allowed ... Important Notice. Gaijin does not restrict the Creator from running advertisements or conducting other profit-generating activities via standard platform monetization systems (for example, YouTube pre-roll ads). However, Gaijin reserves the right to request immediate removal of content that is monetized through any other means not specified in these Guidelines."
- **1.1.4 No Pre-Release Content:** leaked or unreleased content is prohibited.
- **1.1.5 No Extraction of Game(s) Elements:** "Creators are not allowed to extract materials from the Game(s) (such as voice recordings, music, or in-game objects) and distribute them separately in any form."
- **1.1.6 No Plagiarism:** "Creators are not allowed to copy official trailers or create Content based on static in-game images without making a significant creative contribution."
- **1.1.7 No Usage of Names, Logos, Trademarks:** "Creators are not allowed to use any designations related to the Game(s) (including, but not limited to, the Game(s) title and logo) to distinguish products other than the Game(s), whether for profit or not, without Gaijin's prior consent. Additionally, Creators are not allowed to register such designations (identical or confusingly similar) as trademarks and/or **domain names (including sub-domains)** in any country in the world."
- **1.1.8 No Software Modification:** "Creators are not allowed to decompile the Game(s) or perform any other actions for which the Game(s) was not originally intended in order to gain access to materials..."
- **1.2:** some content, such as music, is licensed from third parties.
- **3.3:** "These Guidelines apply solely to individual Creators and do not extend to legal entities, companies, or other organizations of any kind."
- **Contact:** contentpartners@gaijin.net

### Practical reading (my interpretation, not legal advice)

- **Screenshots and your own map traces** are covered as "Gaijin Materials" usable in free, public, non-commercial fan content. That comes with conditions:
    - a "significant creative contribution" (annotations or overlays on a static image arguably qualify);
    - no implied endorsement;
    - the site stays free to access.
- Standard ad monetization appears tolerated. Paywalls or subscriptions are not covered.
- **Risky or prohibited:**
    - datamining or extracting map textures and coordinates from game files (EULA 3.2.9, Guidelines 1.1.5 and 1.1.8, ToS 4.3.2);
    - building a "database" from game content (ToS 4.1 explicitly names "database creation" alongside AI; this could be read broadly; **flag for review**);
    - **using "Active Matter" in a domain or brand name** (Guidelines 1.1.7).
- The official map images on the RU Fandom and wiki.gg are _hosted_ under the wikis' CC BY-SA terms. The wiki license does **not** override Gaijin's copyright in the underlying screenshots. Wiki _text and markers_ written by contributors are CC BY-SA, so reuse needs attribution and share-alike.
- **Safest route:** email contentpartners@gaijin.net for written permission. Gaijin runs a content-creator program ("The Agency Is Recruiting Content Creators!", news/326; partnership form at https://activematter.game/en/partnership).
- **Not found:** no Active-Matter-specific fan-content policy, fan kit, or map-image licence exists on activematter.game.

---

## 8. Public APIs and datamined datasets

- **Official public API:** **none found.** The official site is a Nuxt app. News is server-rendered HTML at `https://activematter.game/{lang}/news/{id}` (sequential IDs, languages en/de/es/fr/pt/ru/zh). That makes it practical to watch for patch notes, but it is not a documented API. Patch-note images come from `patchnotes.cdn.gaijin.net`. No player-stats or items API was found.
- **Community wiki APIs:**
    - wiki.gg MediaWiki API: `https://activematter.wiki.gg/api.php` (CC BY-SA 4.0). Structured infobox fields include Rarity, Tags, Ref (refine value), Weight, Volume and Price.
    - Fandom APIs: `https://activemattergame.fandom.com/api.php` and `https://active-matter.fandom.com/ru/api.php` (CC BY-SA). The RU interactive maps can be read as JSON through `action=query&prop=revisions&titles=Карта:Щегольское&rvslots=main`. Each map has mapBounds of about 1770x1770 and an xy/bottom-left origin, with category and marker lists.
- **Datamined data:**
    - Devs acknowledged "a datamined satellite composite of Dalniy Island showing the full landmass versus the current playable areas" shared by players (news/270). I did **not** locate the file.
    - activematterhelp.ru's about 5,600 POIs are of **unknown provenance**, possibly datamined, possibly hand-placed.
    - No public GitHub datamine repo was found.
    - Datamining is prohibited by EULA 3.2.9.

---

## 9. Unverified or open items (do not ship without confirming)

1. Which region **Headquarters** belongs to (Africa/Anguka Anga or Dalniy Island). The official text is ambiguous.
2. Whether **Military Base** and **Scrapyard** are on Dalniy Island. Only wiki.gg says so; no official statement.
3. Named extraction points: **no official names exist** in any source I found. Community descriptions are positional only.
4. The exact current Open-Raid rotation, and whether variants (Overgrowth, Distortion, Darkness, Hive, Midnight, Silent Observers) are all still live.
5. "Escape from Dogorsk", "Military Base: Silent Observers", and "Euclid" (removed raid): names confirmed, status unknown.
6. Reddit community content: blocked (403), not reviewed.
7. Official Discord content: not reviewed (requires login).
8. Airport, Downtown and Headquarters POIs are thin. There are no wiki raid pages for them.
9. Legal: whether ToS 4.1's "database creation" clause applies to a fan item database. Ask Gaijin.
10. The Steam review figures move daily.

---

## Appendix A: Official news articles most useful for map data

- 10: Shegolskoe
- 13: Ozernoe
- 14: Military Base, beacons, safe container
- 31: EA features
- 81: America: Collapse
- 85: Cargo Port: Deep Cover
- 91: Factory: Bloodbath
- 97: HQ: Code 2901
- 104: Fire Walk (Dam, Dogorsk)
- 140: Turned Soldiers
- 147: Ozernoe: Firestorm
- 156: Flowermen
- 170: Lone Wolf
- 171: Park
- 184: Gigastructure
- 185: Alpha Flowerman
- 188: Distorted Hunter
- 192: Gigastructure update (Park, Vanilla Mall)
- 193: Gigastructure report
- 223: **Tour of Locations**
- 237: Dogorsk: Timeline Collision
- 256 and 263: HQ: Assistance Protocol
- 270, 284, 302, 316: Developer Q&As
- 274 and 279: Downtown
- 276: Safe containers and beacons
- 291: **250 Shades of Liberty** (Downtown, Airport)
- 315: 0.4.0.93, the "Anguka Anga" mention
- 318: Launch
- 336: Fundamental Protocol
- 339: 0.4.0.156

All at `https://activematter.game/en/news/<id>`.

## Appendix B: wiki.gg item categories with rarity (source: activematter.wiki.gg category API, pulled 2026-09-29, CC BY-SA 4.0, [COM], conf M)

Format: item [rarity per wiki category; "?" = no rarity category on the wiki].

- **Civil items** (247; https://activematter.wiki.gg/wiki/Category:Civil_items): "Komsomolets" [Common]; "Tourist" radio [Common]; 'Amfiton' cassette tape player [Uncommon]; 'Amfiton' cassette tape recorder [Uncommon]; 'Aurora' home movie camera [Uncommon]; 'Legenda-404' cassette tape recorder [Uncommon]; 'Zenit' camera [Uncommon]; A Prayer (Part 1 of 2) [Common]; A Prayer (Part 2 of 2) [Common]; A Soldier's Letter [Common]; AA battery [Common]; AChS-1 Clock [Uncommon]; AChS-1 on the stand [Uncommon]; AChS-1 with an airplane [Uncommon]; AM stabilizer solution [Uncommon]; APC Toy [Uncommon]; About safe repairs [Common]; Accumulator battery [Common]; Air freshener [Common]; Album with coins [Uncommon]; Alcohol burner [Common]; Amber Application Act [Common]; Angle grinder [Uncommon]; Axe [Common]; Balloon jar [Common]; Banner [Common]; Big tool case [Common]; Blood analyzer [Common]; Book [Common]; Booklet [Common]; Books [Uncommon]; Botanist's Log [Common]; Boxed AChS-1 [Uncommon]; Broken hammer [Common]; Brush [Common]; Bubble gum [Common]; CD [Uncommon]; Calendar with a mark [Common]; Calipers [Common]; Capacitor KBG-MN [Uncommon]; Capacitor KBGCH-1 [Uncommon]; Car battery [Common]; Car documents [Common]; Car spare part [Common]; Carpet beater [Common]; Cassette [Uncommon]; Cell phone [Uncommon]; Chain [Common]; Child's drawing [Common]; Chisel [Common]; Christmas Toy [Uncommon]; Chronotraces Container [?]; Coffee can [Common]; Computer mouse [Uncommon]; Cooking cleaver [Common]; Cooking knife [Common]; Cordless drill [Uncommon]; Cordless jigsaw [Uncommon]; Crowbar [Common]; Cup [Common]; Cutlery [Common]; Dal-Sokolov Correspondence (1 of 2) [Common]; Dal-Sokolov Correspondence (2 of 2) [Common]; Dead battery [Common]; Deck of Playing Cards [Uncommon]; Defibrillator [Common]; Dental instrument set [Common]; Denunciation of the Airport Chief [Common]; Diary [Common]; Diary page about a car [Common]; Dictaphone [Uncommon]; Diode D248B [Uncommon]; Diskette with experiments results (Greenhouse bunker) [Rare]; Diskette with games [Uncommon]; Diskette with laboratory data [Uncommon]; Disposable camera [Common]; Doctor's notes [Common]; Dreamcatcher [Uncommon]; Duct tape [Common]; Electric shaver [Common]; Electronic components [Common]; Electronics [Common]; Elektronika MK-52 [Uncommon]; Elektronika MK-59 [Uncommon]; Empty spool [Common]; Enzyme deactivator Silence [Uncommon]; Extension cord [Uncommon]; Figurine [Uncommon]; Finish flag [Common]; Fire extinguisher [Common]; Fishing rod [Uncommon]; Flag [Common]; Fuse [Uncommon]; Game Cartridge [Common]; Gas canister [Common]; Gears [Common]; Glass cutter [Common]; Great Encyclopedia [Uncommon]; Hammer drill [Uncommon]; Hand plane [Common]; Handkerchief [Common]; Handwritten note [Common]; Handwritten notes [Common]; Headphones [Uncommon]; Hunting matches [Common]; Incident Report [Common]; Industrial scrap [Common]; Internal Memo [Common]; Jug [Common]; Kitchen utensils [Common]; LR20 battery [Common]; Laboratory counter SL-1 [Common]; Laboratory shift log [Common]; Letter from Savushkina to Her Grandson (1 of 2) [Common]; Letter from Savushkina to Her Grandson (2 of 2) [Common]; Letter to the Quartermaster [Common]; Light bulb [Common]; Love Letter [Common]; MPL50 Shovel [Common]; Map of the facility (Greenhouse bunker) [Common]; Matchbox [Common]; Meat grinder [Common]; Medical IV drip [Common]; Memo about fuel [Common]; Metal nuts box [Common]; Metal thing [Common]; Micrometer [Common]; Multimeter [Uncommon]; Mutagenic catalyst Rost-1 [Uncommon]; Nails [Common]; Neighbor's Note [Common]; Note about gas station [Common]; Note about the garage [Common]; Notebook for police reports [Common]; Notes about Medication [Common]; Notes about weapons [Common]; Observation Protocol [Common]; Old bandage [Common]; Old canteen [Common]; Old chemicals [Common]; Old drugs and chemicals [Common]; Old hunting shells [Common]; Old iodine [Common]; Old pills [Common]; Old ticket [Common]; Oscillograph [Uncommon]; PP3 battery [Common]; Padlock [Common]; Page from "Severniy Veter" ship's log 1 [Common]; Pager [Uncommon]; Pan flute [Uncommon]; Paper clips [Common]; Photofilm cartridge [Uncommon]; Pincers [Common]; Pins [Common]; Pins Album [Uncommon]; Pipe wrench [Common]; Planner Page [Common]; Plastic handcuffs [Common]; Plate [Common]; Plates [Common]; Pliers [Common]; Plush [Uncommon]; Pocket TV [Uncommon]; Police radio [Common]; Police tape [Common]; Private's Observation Log (1 of 2) [Common]; Private's Observation Log (2 of 2) [Common]; Protein denaturing reagent [Uncommon]; Pruner [Common]; Quarter [Uncommon]; RKSB-104 Dosimeter [Uncommon]; Radio [Common]; Rag [Common]; Records of the laboratory assistant on duty [Uncommon]; Red LED [Common]; Repair kit [Common]; Report on the Failed Evacuation Drill [Common]; Researcher's Diary I (1 of 2) [Common]; Researcher's Diary I (2 of 2) [Common]; Researcher's Diary II (1 of 2) [Common]; Researcher's Diary II (2 of 2) [Common]; Researcher's Diary III (1 of 2) [Common]; Researcher's Diary III (2 of 2) [Common]; Researcher's Diary IV (1 of 2) [Common]; Researcher's Diary IV (2 of 2) [Common]; Resistor PEV-25 [Common]; Resistor SP-II [Common]; Resistors MLT-II [Common]; Reward cup [Uncommon]; Rugs [Common]; Ruler (Household) [Common]; Ruler (Industrial) [Common]; Rusty iron [Common]; Rusty nut [Common]; Saucer [Common]; Screwdriver [Common]; Search Operation Report (1 of 2) [Common]; Search Operation Report (2 of 2) [Common]; Set of surgical instruments [Common]; Shipping manifest 361 [Common]; Shipping manifest 745 [Common]; Shopping List [Common]; Slide rule [Common]; Small household goods [Common]; Soap [Common]; Solution for nutrient medium [Uncommon]; Spool of thread [Common]; Spore inhibitor Barrier [Uncommon]; Sputnik Radio Toy Car [Uncommon]; Square [Common]; Stamps Album [Uncommon]; Teapot [Common]; Tin [Common]; Toy controller [Uncommon]; Toy robot [Uncommon]; Toy train [Uncommon]; Transistor P2033 [Uncommon]; Trial CD [Common]; Troitsky-Tupolev Correspondence (1 of 2) [Common]; Troitsky-Tupolev Correspondence (2 of 2) [Common]; Tureen [Common]; Unsigned VHS cassette [Uncommon]; VHS cassette "7 Shoguns" [Uncommon]; VHS cassette "A Clockwork Tomato" [Uncommon]; VHS cassette "Cybernetic Militiaman" [Uncommon]; VHS cassette "Diamond Leg" [Uncommon]; VHS cassette "Forward to the Past" [Uncommon]; VHS cassette "Giraffe King" [Uncommon]; VHS cassette "Snail Lake" [Uncommon]; Vase [Uncommon]; Vaselinum [Common]; Vinyl disk "Morning" [Uncommon]; Vinyl disk "New Ways Forward" [Uncommon]; Vinyl disk "Nights in the City" [Uncommon]; Vinyl disk "Pink Clouds" [Uncommon]; Vinyl disk "Ride of Valkyries" [Uncommon]; Vinyl disk "Shady Operation" [Uncommon]; Vinyl disk "Team Spirit" [Uncommon]; Vinyl disk "The Revelation" [Uncommon]; Vinyl disk "Up the Hill" [Uncommon]; Waffle maker [Common]; Walkie-talkie [Common]; Weapon specifications documents [Rare]; Weighing scale [Common]; Wires [Common]; Worn-out note [Common]
- **Electronics** (43; https://activematter.wiki.gg/wiki/Category:Electronics): "Tourist" radio [Common]; 'Amfiton' cassette tape player [Uncommon]; 'Amfiton' cassette tape recorder [Uncommon]; 'Aurora' home movie camera [Uncommon]; 'Legenda-404' cassette tape recorder [Uncommon]; AA battery [Common]; APC Toy [Uncommon]; Blood analyzer [Common]; Capacitor KBG-MN [Uncommon]; Capacitor KBGCH-1 [Uncommon]; Cell phone [Uncommon]; Computer mouse [Uncommon]; Defibrillator [Common]; Dictaphone [Uncommon]; Diode D248B [Uncommon]; Diskette with experiments results (Greenhouse bunker) [Rare]; Diskette with games [Uncommon]; Diskette with laboratory data [Uncommon]; Disposable camera [Common]; Electronic components [Common]; Electronics [Common]; Elektronika MK-52 [Uncommon]; Elektronika MK-59 [Uncommon]; Headphones [Uncommon]; LR20 battery [Common]; Light bulb [Common]; Multimeter [Uncommon]; Oscillograph [Uncommon]; PP3 battery [Common]; Pager [Uncommon]; Pocket TV [Uncommon]; Police radio [Common]; RKSB-104 Dosimeter [Uncommon]; Radio [Common]; Red LED [Common]; Resistor PEV-25 [Common]; Resistor SP-II [Common]; Resistors MLT-II [Common]; Rusty iron [Common]; Sputnik Radio Toy Car [Uncommon]; Toy controller [Uncommon]; Transistor P2033 [Uncommon]; Walkie-talkie [Common]
- **Electronics components** (12; https://activematter.wiki.gg/wiki/Category:Electronics_components): AA battery [Common]; Capacitor KBG-MN [Uncommon]; Capacitor KBGCH-1 [Uncommon]; Diode D248B [Uncommon]; Electronic components [Common]; LR20 battery [Common]; Light bulb [Common]; PP3 battery [Common]; Red LED [Common]; Resistor PEV-25 [Common]; Resistors MLT-II [Common]; Transistor P2033 [Uncommon]
- **Industrial items** (25; https://activematter.wiki.gg/wiki/Category:Industrial_items): Big tool case [Common]; Broken hammer [Common]; Brush [Common]; Calipers [Common]; Car battery [Common]; Car spare part [Common]; Chain [Common]; Dead battery [Common]; Duct tape [Common]; Gas canister [Common]; Gears [Common]; Glass cutter [Common]; Hand plane [Common]; Industrial scrap [Common]; Metal nuts box [Common]; Micrometer [Common]; Nails [Common]; Pincers [Common]; Pliers [Common]; Rag [Common]; Repair kit [Common]; Ruler (Industrial) [Common]; Rusty nut [Common]; Square [Common]; Wires [Common]
- **Chemicals** (12; https://activematter.wiki.gg/wiki/Category:Chemicals): AM stabilizer solution [Uncommon]; Enzyme deactivator Silence [Uncommon]; Mutagenic catalyst Rost-1 [Uncommon]; Old chemicals [Common]; Old drugs and chemicals [Common]; Old iodine [Common]; Old pills [Common]; Protein denaturing reagent [Uncommon]; Soap [Common]; Solution for nutrient medium [Uncommon]; Spore inhibitor Barrier [Uncommon]; Vaselinum [Common]
- **Kitchen items** (13; https://activematter.wiki.gg/wiki/Category:Kitchen_items): Coffee can [Common]; Cup [Common]; Cutlery [Common]; Jug [Common]; Kitchen utensils [Common]; Meat grinder [Common]; Plate [Common]; Plates [Common]; Saucer [Common]; Teapot [Common]; Tin [Common]; Tureen [Common]; Waffle maker [Common]
- **Medical items** (11; https://activematter.wiki.gg/wiki/Category:Medical_items): AI-2 First Aid kit [Common]; Big first aid kit [Rare]; Burst injector [Uncommon]; Driver's first aid kit [Common]; First aid kit [Common]; Healing ampule [Common]; Medical bag [Rare]; Painkiller [Common]; Regeneration injector [Common]; Rescue kit [Common]; Streaming injector [Uncommon]
- **Tools** (31; https://activematter.wiki.gg/wiki/Category:Tools): Angle grinder [Uncommon]; Axe [Common]; Big tool case [Common]; Broken hammer [Common]; Brush [Common]; Calipers [Common]; Carpet beater [Common]; Chisel [Common]; Cooking cleaver [Common]; Cooking knife [Common]; Cordless drill [Uncommon]; Cordless jigsaw [Uncommon]; Crowbar [Common]; Extension cord [Uncommon]; Glass cutter [Common]; Hammer drill [Uncommon]; Hand plane [Common]; MPL50 Shovel [Common]; Micrometer [Common]; Multimeter [Uncommon]; Paladin's Crusade [?]; Pincers [Common]; Pipe wrench [Common]; Pliers [Common]; Pruner [Common]; Repair kit [Common]; Ruler (Household) [Common]; Ruler (Industrial) [Common]; Screwdriver [Common]; Square [Common]; Weighing scale [Common]
- **Keys** (40; https://activematter.wiki.gg/wiki/Category:Keys): AM vault key [Rare]; Armory 1 key [Rare]; Armory 2 key [Rare]; Armory key [Rare]; Basement key [Rare]; Big military crate key [Rare]; Bunker south entrance key [Rare]; Captain's key [Rare]; Chest key [Rare]; Cinema storage key [Rare]; East storage key [Rare]; Fire station safe key [Rare]; Hangar 148 key [Rare]; Hangar 17 key [Rare]; Hangar 18 key [Rare]; Hangar 209 key [Rare]; Hangar 29 key [Rare]; Hangar 35 key [Rare]; Hangar 84 key [Rare]; Key-Memory Card [Rare]; Keys with a note [Rare]; Lab key [Rare]; Lab safe key [Rare]; Lock actuator board [Rare]; Metal door key [Rare]; Port crane key [Rare]; Room key [Rare]; Safe key [Rare]; Scientist's safe key [Rare]; Shegolskoe 11 attic key [Rare]; Shegolskoe 15 attic key [Rare]; Shegolskoe 8 attic key [Rare]; Small military crate key [Rare]; Storage room key [Uncommon]; Synchronization Device (Tier 1) [Uncommon]; Synchronization Device (Tier 2) [Rare]; The key to the safe. [Rare]; Underground laboratory key [Rare]; Warehouse room key [Rare]; West storage key [Rare]
- **Remains** (20; https://activematter.wiki.gg/wiki/Category:Remains): Altered wax [Uncommon]; Pristine altered wax (Active Matter Gathering improvement) [Rare]; Pristine remains of Dendroid (Melee Strength improvement) [Rare]; Pristine remains of Devourer (Bullet Proof improvement) [Rare]; Pristine remains of Distorted (Jump improvement) [Rare]; Pristine remains of Flowerman (Speed improvement) [Rare]; Pristine remains of Hellhound (camouflage improvement: Cold-blooded) [Rare]; Pristine remains of Invisible (Carrying Capacity improvement) [Rare]; Pristine remains of Scorch [Rare]; Pristine remains of Shy Girl [Rare]; Pristine remains of Turned Soldier (Examination improvement) [Rare]; Remains of Dendroid [Uncommon]; Remains of Devourer [Uncommon]; Remains of Distorted [Uncommon]; Remains of Flowerman [Uncommon]; Remains of Hellhound [Uncommon]; Remains of Invisible [Uncommon]; Remains of Scorch [Uncommon]; Remains of Shy Girl [Uncommon]; Remains of Turned Soldier [Uncommon]
- **Contract items** (80; https://activematter.wiki.gg/wiki/Category:Contract_items): "Rosin" sample [Rare]; Administrator documents [Rare]; Agronomist's notes [Rare]; American Workman [Rare]; Anomalous plant sample [Rare]; Anomalous water samples [Rare]; Basement key [Rare]; Cargo manifest [Rare]; Cargo movement logbook [Rare]; Chemical storage documents [Rare]; Chest key [Rare]; Classified weapon blueprints (Item) [Rare]; Courier manifest [Rare]; Courier's bag [Rare]; Data card [Rare]; Document stash [Rare]; Duty log [Rare]; Field journal [Rare]; Field orders (unit 14) [Rare]; Fire station reports [Rare]; Fire station safe key [Rare]; Firefighters recording [Rare]; Flashdrive with access codes [Rare]; Folder with money and a note [Rare]; Gardener's diary [Rare]; Greenhouse lab notes [Rare]; Groshin's Work Diary [Rare]; Groshin's tissue sample [Rare]; Ground samples [Rare]; Ground samples from Ozernoe [Rare]; Growth Activator [Rare]; Handyman's receipt [Rare]; Headquarters data [Rare]; Helmet liner [Rare]; Helmet liner fragment [Rare]; Herbicide "Phoenix" [Rare]; Icarus cargo manifest [Rare]; Incident report [Rare]; Incident witness testimony [Rare]; Institute Documents [Rare]; Irina's beekeeping tools [Rare]; Journal of Laboratory Experiments [Rare]; Key-Memory Card [Rare]; Lab employee ID [Rare]; Lab key [Rare]; Major Sokolov's radio [Rare]; Medic's field log [Rare]; Medical personnel evacuation order [Rare]; Medical records [Rare]; Metal door key [Rare]; Military device [Rare]; Monolith Emission Scanner [Rare]; Mutation Stabilizer [Rare]; Observation journal [Rare]; Page from "Severniy Veter" ship's log 2 [Rare]; Pallet tracking slip [Rare]; Personal notes by Lydia Orlova [Rare]; Port operations documents [Rare]; Port security chief documents [Rare]; Preserved root cluster [Rare]; Researcher's notebook [Rare]; Residue of altered wax [Rare]; SMG parts crate [Rare]; Sample "AM-F1" [Rare]; Scientist's safe key [Rare]; Soil samples from Dogorsk [Rare]; Soldier's dog tag [Rare]; Surveillance diary [Rare]; Surveillance documents [Rare]; Technician's notes [Rare]; The key to the safe. [Rare]; Transfer documents [Rare]; Valuable cargo [Rare]; Vessel movement register [Rare]; Warehouse room key [Rare]; Watchman's keyring [Rare]; Water samples [Rare]; Weird seeds [Rare]; Work schedule [Rare]; Worker's broken tools [Rare]
- **Notes** (36; https://activematter.wiki.gg/wiki/Category:Notes): "Komsomolets" [Common]; A Prayer (Part 1 of 2) [Common]; A Prayer (Part 2 of 2) [Common]; A Soldier's Letter [Common]; Amber Application Act [Common]; Botanist's Log [Common]; Dal-Sokolov Correspondence (1 of 2) [Common]; Dal-Sokolov Correspondence (2 of 2) [Common]; Denunciation of the Airport Chief [Common]; Groshin's Work Diary [Rare]; Incident Report [Common]; Internal Memo [Common]; Letter from Savushkina to Her Grandson (1 of 2) [Common]; Letter from Savushkina to Her Grandson (2 of 2) [Common]; Letter to the Quartermaster [Common]; Love Letter [Common]; Map of the facility (Greenhouse bunker) [Common]; Neighbor's Note [Common]; Observation Protocol [Common]; Port operations documents [Rare]; Private's Observation Log (1 of 2) [Common]; Private's Observation Log (2 of 2) [Common]; Report on the Failed Evacuation Drill [Common]; Researcher's Diary I (1 of 2) [Common]; Researcher's Diary I (2 of 2) [Common]; Researcher's Diary II (1 of 2) [Common]; Researcher's Diary II (2 of 2) [Common]; Researcher's Diary III (1 of 2) [Common]; Researcher's Diary III (2 of 2) [Common]; Researcher's Diary IV (1 of 2) [Common]; Researcher's Diary IV (2 of 2) [Common]; Search Operation Report (1 of 2) [Common]; Search Operation Report (2 of 2) [Common]; Shopping List [Common]; Troitsky-Tupolev Correspondence (1 of 2) [Common]; Troitsky-Tupolev Correspondence (2 of 2) [Common]
- **Cassette tapes** (9; https://activematter.wiki.gg/wiki/Category:Cassette_tapes): Cassette [Uncommon]; Unsigned VHS cassette [Uncommon]; VHS cassette "7 Shoguns" [Uncommon]; VHS cassette "A Clockwork Tomato" [Uncommon]; VHS cassette "Cybernetic Militiaman" [Uncommon]; VHS cassette "Diamond Leg" [Uncommon]; VHS cassette "Forward to the Past" [Uncommon]; VHS cassette "Giraffe King" [Uncommon]; VHS cassette "Snail Lake" [Uncommon]
- **Vinyl record** (9; https://activematter.wiki.gg/wiki/Category:Vinyl_record): Vinyl disk "Morning" [Uncommon]; Vinyl disk "New Ways Forward" [Uncommon]; Vinyl disk "Nights in the City" [Uncommon]; Vinyl disk "Pink Clouds" [Uncommon]; Vinyl disk "Ride of Valkyries" [Uncommon]; Vinyl disk "Shady Operation" [Uncommon]; Vinyl disk "Team Spirit" [Uncommon]; Vinyl disk "The Revelation" [Uncommon]; Vinyl disk "Up the Hill" [Uncommon]
- **VHS cassettes** (8; https://activematter.wiki.gg/wiki/Category:VHS_cassettes): Unsigned VHS cassette [Uncommon]; VHS cassette "7 Shoguns" [Uncommon]; VHS cassette "A Clockwork Tomato" [Uncommon]; VHS cassette "Cybernetic Militiaman" [Uncommon]; VHS cassette "Diamond Leg" [Uncommon]; VHS cassette "Forward to the Past" [Uncommon]; VHS cassette "Giraffe King" [Uncommon]; VHS cassette "Snail Lake" [Uncommon]
- **Books** (6; https://activematter.wiki.gg/wiki/Category:Books): Book [Common]; Books [Uncommon]; Diary [Common]; Great Encyclopedia [Uncommon]; Laboratory shift log [Common]; Records of the laboratory assistant on duty [Uncommon]
- **Clocks** (4; https://activematter.wiki.gg/wiki/Category:Clocks): AChS-1 Clock [Uncommon]; AChS-1 on the stand [Uncommon]; AChS-1 with an airplane [Uncommon]; Boxed AChS-1 [Uncommon]
- **Radios** (3; https://activematter.wiki.gg/wiki/Category:Radios): "Tourist" radio [Common]; Electronics [Common]; Radio [Common]
- **Tape recorders** (3; https://activematter.wiki.gg/wiki/Category:Tape_recorders): 'Amfiton' cassette tape player [Uncommon]; 'Amfiton' cassette tape recorder [Uncommon]; 'Legenda-404' cassette tape recorder [Uncommon]
- **Containers** (8; https://activematter.wiki.gg/wiki/Category:Containers): "Jewel Box" [?]; "Nightstand" [?]; Container with samples [?]; Dead scientist [?]; Full "rosin" barrel [?]; Headquarters' computer [?]; Suitcase [?]; Timeline Scar [?]
- **Currencies** (1; https://activematter.wiki.gg/wiki/Category:Currencies): Credit [?]
- **Park lab reagents** (6; https://activematter.wiki.gg/wiki/Category:Park_lab_reagents): AM stabilizer solution [Uncommon]; Enzyme deactivator Silence [Uncommon]; Mutagenic catalyst Rost-1 [Uncommon]; Protein denaturing reagent [Uncommon]; Solution for nutrient medium [Uncommon]; Spore inhibitor Barrier [Uncommon]
- **Rusty** (29; https://activematter.wiki.gg/wiki/Category:Rusty): Broken hammer [Common]; Gears [Common]; Hand plane [Common]; Nails [Common]; Old hunting shells [Common]; Padlock [Common]; Pincers [Common]; Pliers [Common]; Pruner [Common]; Ruler (Industrial) [Common]; Rusty AK-74 magazine [Common]; Rusty AK-74M [Common]; Rusty AKM [Common]; Rusty AKM magazine [Common]; Rusty AKS-74U [Common]; Rusty M1911A1 [Common]; Rusty M4 [Common]; Rusty M4 magazine [Common]; Rusty Makarov pistol [Common]; Rusty Mosin carbine [Common]; Rusty Mosin rifle [Common]; Rusty SKS carbine [Common]; Rusty SSh-40 [Common]; Rusty armor plate (L) [Common]; Rusty armor plate (S) [Common]; Rusty armor plate (XL) [Common]; Rusty bayonet Knife [Common]; Rusty iron [Common]; Rusty nut [Common]
- **Saturated** (11; https://activematter.wiki.gg/wiki/Category:Saturated): 'Amfiton' cassette tape player [Uncommon]; 'Aurora' home movie camera [Uncommon]; 'Legenda-404' cassette tape recorder [Uncommon]; 'Zenit' camera [Uncommon]; Elektronika MK-52 [Uncommon]; Elektronika MK-59 [Uncommon]; Fishing rod [Uncommon]; Great Encyclopedia [Uncommon]; RKSB-104 Dosimeter [Uncommon]; Stamps Album [Uncommon]; Vase [Uncommon]
- **Special** (4; https://activematter.wiki.gg/wiki/Category:Special): Broken gravity gun [Rare]; Fire extinguisher [Common]; Gravity gun [Rare]; Hunting crossbow [Common]
- **Armor plates** (15; https://activematter.wiki.gg/wiki/Category:Armor_plates): Ceramic armor plate (L) [Uncommon]; Ceramic armor plate (S) [Uncommon]; Ceramic armor plate (XL) [Uncommon]; Rusty armor plate (L) [Common]; Rusty armor plate (S) [Common]; Rusty armor plate (XL) [Common]; Steel armor plate (L) [Common]; Steel armor plate (S) [Common]; Steel armor plate (XL) [Common]; Titan armor plate (L) [Rare]; Titan armor plate (S) [Rare]; Titan armor plate (XL) [Rare]; UHMWPE armor plate (L) [Common]; UHMWPE armor plate (S) [Common]; UHMWPE armor plate (XL) [Common]
- **Backpacks** (7; https://activematter.wiki.gg/wiki/Category:Backpacks): Army Backpack [Common]; Large Backpack [Rare]; Medium Backpack [Uncommon]; Rag Backpack [Common]; Small Backpack [Common]; USSR Climber's Backpack [Rare]; USSR Hiking Backpack [Uncommon]
- **Helmets** (19; https://activematter.wiki.gg/wiki/Category:Helmets): A3 Helmet [Common]; Ballistic helmet TFMK-I [Rare]; Enhanced Helmet EDH-Gen V [Uncommon]; IHPS Helmet [Rare]; K6 Helmet [Rare]; K6-3 Helmet [Rare]; KVR Helmet [Common]; M1 Helmet [Common]; PASGT Helmet [Common]; Rusty SSh-40 [Common]; SF Helmet [Uncommon]; SSh-40 [Common]; Stahlhelm [Common]; Tactical Helmet TRM-73 [Common]; Tactical PASGT Helmet [Common]; Tactical headset [Common]; Tank crew helmet [Common]; User:Prof. Sugarcube/Sandbox [Uncommon]; Winter Hat [Common]
- **Pouches** (7; https://activematter.wiki.gg/wiki/Category:Pouches): Large Magazine Rig [Rare]; Large Rig [Rare]; Medium Magazine Rig [Uncommon]; Medium Rig [Uncommon]; Small Magazine Rig [Common]; Small Medicine Rig [Common]; Small Rig [Common]
- **Safe containers** (3; https://activematter.wiki.gg/wiki/Category:Safe_containers): Large safe container [Rare]; Medium safe container [Rare]; Small safe container [Rare]
- **Grenades** (6; https://activematter.wiki.gg/wiki/Category:Grenades): F-1 grenade [Common]; Incendiary grenade [Common]; M8 Smoke Grenade [Common]; Molotov Cocktail [Common]; RGD-M Smoke grenade [Common]; Zarya 3 stun grenade [Common]
- **Magazines** (64; https://activematter.wiki.gg/wiki/Category:Magazines): AK 60 rds. magazine [Rare]; AK 6L26 45 rds. magazine [Uncommon]; AK-103 magazine [Common]; AK-12 magazine [Common]; AK-74 magazine [Common]; AKM magazine [Common]; AS Val 30 rds. magazine [Uncommon]; AS Val magazine [Common]; ASh-12 magazine [Common]; Bakelite AKM magazine [Common]; EV-MG ammo pouch [Common]; G3A4 magazine [Common]; Gepard magazine [Common]; M110A1 10 rds. magazine [Common]; M110A1 magazine [Uncommon]; M14 magazine [Common]; M1911 10 rds. magazine [Common]; M1911 magazine [Common]; M1928A1 drum [Uncommon]; M1928A1 magazine [Common]; M249 ammo pouch [Common]; M4 magazine [Common]; M4A1 magazine [Common]; M82A1 magazine [Common]; M9 magazine [Common]; MAC-11 magazine [Common]; MP M4 30 rds. magazine [Common]; MP M4 40 rds. magazine [Uncommon]; MP-40 magazine [Common]; MP-443 Grach magazine [Common]; MP5 magazine [Common]; Makarov Pistol magazine [Common]; Origin 12 10 rds. magazine [Rare]; Origin 12 5 rds. magazine [Common]; Origin 12 8 rds. magazine [Uncommon]; P17-V magazine [Common]; PKM ammo box [Common]; PM-63 magazine [Common]; PP-19-01 magazine [Common]; PP-2000 20 rds. magazine [Common]; PP-2000 30 rds. magazine [Common]; PP-2000 44 rds. magazine [Uncommon]; PP-91 20 rds. magazine [Common]; PP-91 30 rds. magazine [Common]; PPSh-41 drum [Rare]; PPSh-41 magazine [Common]; RPK-74 magazine [Common]; RPL-20 ammo pouch [Common]; Rusty AK-74 magazine [Common]; Rusty AKM magazine [Common]; Rusty M4 magazine [Common]; SCAR-H magazine [Common]; SCAR-L magazine [Common]; STG77 magazine [Common]; SV-98 magazine [Common]; SVD magazine [Common]; Scorpion EVO 3 magazine [Common]; Stechkin Pistol magazine [Common]; TT Pistol magazine [Common]; UMP magazine [Common]; VSSK Vykhlop magazine [Common]; Vepr magazine [Common]; XV ACP 40 rds. drum [Uncommon]; XV ACP magazine [Common]
- **Night Vision Devices** (2; https://activematter.wiki.gg/wiki/Category:Night_Vision_Devices): Night vision device [Uncommon]; PNV-57E Night vision device [Uncommon]
- **Flashlights** (3; https://activematter.wiki.gg/wiki/Category:Flashlights): Chest Light [Common]; Flashlight [Common]; MX991/U Flashlight [Common]
- **Signal grenades** (2; https://activematter.wiki.gg/wiki/Category:Signal_grenades): Quad bike signal grenade [Rare]; Robot Dog signal grenade [Uncommon/Epic]
- **Consumables** (1; https://activematter.wiki.gg/wiki/Category:Consumables): Beacon [Rare]
- **Newspapers** (2; https://activematter.wiki.gg/wiki/Category:Newspapers): "Komsomolets" [Common]; Paladin's Crusade [?]
- **Matchboxes** (2; https://activematter.wiki.gg/wiki/Category:Matchboxes): Hunting matches [Common]; Matchbox [Common]
- **Vases** (1; https://activematter.wiki.gg/wiki/Category:Vases): Vase [Uncommon]
- **Compact discs** (1; https://activematter.wiki.gg/wiki/Category:Compact_discs): Trial CD [Common]
- **Christmas tree toys** (1; https://activematter.wiki.gg/wiki/Category:Christmas_tree_toys): Christmas Toy [Uncommon]
- **APC toys** (1; https://activematter.wiki.gg/wiki/Category:APC_toys): APC Toy [Uncommon]
- **Pins album** (1; https://activematter.wiki.gg/wiki/Category:Pins_album): Pins Album [Uncommon]
- **Dogorsk: Timeline Collision** (16; https://activematter.wiki.gg/wiki/Category:Dogorsk:_Timeline_Collision): 7.62x25mm ammo box [Common]; Fracture Shard [Rare]; M1 Helmet [Common]; M1928A1 [Common]; M1928A1 drum [Uncommon]; M1928A1 magazine [Common]; MP-40 [Common]; MP-40 magazine [Common]; PPSh-41 [Common]; PPSh-41 drum [Rare]; PPSh-41 magazine [Common]; Stahlhelm [Common]; TT Pistol [Uncommon]; TT Pistol magazine [Common]; Timeline Scar [?]; Winter Hat [Common]
- **Cargo Port keys** (3; https://activematter.wiki.gg/wiki/Category:Cargo_Port_keys): Big military crate key [Rare]; Port crane key [Rare]; Small military crate key [Rare]
- **Dam keys** (5; https://activematter.wiki.gg/wiki/Category:Dam_keys): Captain's key [Rare]; East storage key [Rare]; Lock actuator board [Rare]; Safe key [Rare]; West storage key [Rare]
- **Dogorsk keys** (4; https://activematter.wiki.gg/wiki/Category:Dogorsk_keys): Cinema storage key [Rare]; Hangar 29 key [Rare]; Hangar 35 key [Rare]; Lock actuator board [Rare]
- **Factory keys** (2; https://activematter.wiki.gg/wiki/Category:Factory_keys): Hangar 17 key [Rare]; Hangar 18 key [Rare]
- **Military Base keys** (4; https://activematter.wiki.gg/wiki/Category:Military_Base_keys): Armory 1 key [Rare]; Armory 2 key [Rare]; Hangar 148 key [Rare]; Hangar 209 key [Rare]
- **Ozernoe keys** (1; https://activematter.wiki.gg/wiki/Category:Ozernoe_keys): Keys with a note [Rare]
- **Park keys** (5; https://activematter.wiki.gg/wiki/Category:Park_keys): AM vault key [Rare]; Armory key [Rare]; Bunker south entrance key [Rare]; Lab safe key [Rare]; Underground laboratory key [Rare]
- **Scrapyard keys** (1; https://activematter.wiki.gg/wiki/Category:Scrapyard_keys): Hangar 84 key [Rare]
- **Shegolskoe keys** (5; https://activematter.wiki.gg/wiki/Category:Shegolskoe_keys): Keys with a note [Rare]; Shegolskoe 11 attic key [Rare]; Shegolskoe 15 attic key [Rare]; Shegolskoe 4 attic key [Rare]; Shegolskoe 8 attic key [Rare]
