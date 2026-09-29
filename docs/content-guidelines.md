# Content guidelines

These rules apply to everyone who edits data: admins, editors, and contributors sending import files.

## 1. Never invent data

- Do not add a location, extraction point, loot spot, enemy position, objective, item use or mechanic unless you can point to a source or observed it yourself in the current build.
- If you know a place exists but not where it is, create it **without coordinates** (unplaced) and cite the source.
- If a field is unknown, leave it empty. The UI shows "Unknown".
- Do not turn relative directions ("south of the dam") into coordinates. Put them in the description.

## 2. Always record provenance

| Situation                       | Source to use                                                                           | Also fill                                                   |
| ------------------------------- | --------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| Official news, patch notes, FAQ | _Active Matter official news_ / _official FAQ_                                          | `source_url` to the exact article                           |
| Community wiki                  | the wiki's source entry                                                                 | `source_url` to the page; paraphrase, don't paste long text |
| You saw it in game              | create or choose a _player report_ or _admin observation_ source                        | `verified_version` = current build; press **Verify now**    |
| Another fan site                | Only with the maintainer's sign-off (activematterhelp.ru is the one approved exception) | Its own source entry, so confidence stays honest            |

Use `source_note` for a short quote or clarification ("in-game objective text").

### Sources are admin-only

Players never see source names, links or notes. Record provenance in the source fields, which editors can see, **not** in descriptions or field notes, because those are public. Write descriptions as plain facts ("Opened by the Hangar 84 key."), without "(per wiki)" or "(official news)".

## 3. Versioning

- When a patch lands, add it in **Admin → Game versions** and mark it current. Every record not verified on the new build loses 15 confidence points until someone re-verifies it.
- When you confirm a marker in game on the current build, press **Verify now**. This sets `verified_version` and `last_verified_at`.
- If something was removed from the game, set its status to **removed** rather than deleting it. The history is kept.

## 4. Feedback inbox

Work the queue in **Admin → Feedback** (the badge shows the open count):

- **Accept:** the report was right. Fix the data and add a resolution note.
- **Reject:** the report was wrong or spam. Add a note.
- Open reports lower confidence automatically, so resolve them promptly.
- For **suggested positions**, check the spot against the map, then press **Apply position**. This moves the marker and resolves the feedback in one step.
- General feedback (bugs, ideas) has no subject. Reply by email when the player left one, then resolve it.

## 5. Writing style

- Names: use the in-game spelling (for example "Shegolskoe"; note alternate spellings in the description).
- Descriptions: short, factual and player-focused ("Locked; needs the Port crane key.").
- Put conditions in the **Conditions** field (keys, collapse phase, data cards).

## 6. Legal

- No datamined data and no extracted game assets (EULA 3.2.9, Content Creator Guidelines 1.1.5 and 1.1.8).
- Screenshots only as reference for **original** traced map art. Never upload raw screenshots as base layers, and never use other sites' maps.
- Wiki text is CC BY-SA 4.0. Paraphrase, and keep the source link, which provides attribution.
- Don't use the game's logo, and don't present the site as official.
