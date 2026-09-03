# Category Structure — Availability Report & Seed Plan

**Date:** 2026-09-03 · **Site checked:** https://www.avazonia.com (live production)

## 1. What's currently on production

Production has a **partial, flat legacy tree** — 14 top-level entries that grew ad-hoc
(including car *brands* as categories and a broken `mitsubishi-` slug). All product data
sits in 6 of them:

| Legacy category | Slug | Products (direct) | Verdict |
|---|---|---|---|
| Automobile | `automobile` | **38** | No home in final structure → move to **Automotive** |
| Accessories | `mobile-accessories` | **16** | Slug survives → becomes **Mobile Accessories** sub |
| Audio | `audio` | **9** | → **Audio & Headphones** (Electronics) |
| Beauty & Personal Care | `beauty-personal-care` | **9** | Slug survives → same top-level |
| Wearables | `wearables` | **3** | → **Wearable Technology** (Gadgets & Smart Devices) |
| Smart Home | `smart-home-devices` | **1** | Slug survives → **Smart Home Devices** sub |
| *(all other legacy categories)* | — | 0 | Emptied, safe to remove |

**Total: 76 products, all accounted for.** Everything else on production (Smartphones,
Audio children, Automobile brand children `audi`/`honda`/`mitsubishi-`, `energy`,
`industrial-machinery`, `wholesale-general`, `home-living`, `health-medical`,
`health-wellness`, `vehicles-cars`, plus the 2016-era dump categories) has **0 products**.

## 2. Requested vs available

Of the client's **26 top-level + 246 subcategories (272 total)**:

- **AVAILABLE already (8 slugs, but all with the wrong parent/position):**
  `speakers`, `smartphones`, `feature-phones`, `chargers`, `smartwatches`,
  `fitness-trackers`, `smart-home-devices`, `mobile-accessories` — exact matches for
  requested slugs that exist somewhere in the legacy tree. They will be **reparented
  into the final structure**, not recreated.
- **MISSING: everything else (~260).** Nearly the entire final structure is new.

## 3. Seed plan (implemented in `seed_final_categories.php`)

Mode: **replace**, but non-destructive:

1. **Backup** — current `categories` table → `zz_categories_backup`; every product's
   current category → `zz_product_category_backup` (both restorable in the DB).
2. **Wipe** — old tree deleted (products' `category_id` set NULL by the FK).
3. **Seed** — the exact 26/246 final structure with slugs, emoji icons, and sort order.
4. **Remap** — all 76 products moved onto their new category via an explicit
   old-slug → new-slug map (see table in §1; leaf legacy slugs like `android-phones`,
   `vehicles-evs`, `audi`… are also mapped so nothing can fall through).
5. **Verify** — prints totals, per-top-level sub counts, and any unmapped products
   (expected: 0).

Duplicate names across sections get unique slugs: `speakers`/`speakers-2`,
`watches`/`watches-2`, `educational-toys`/`educational-toys-2`,
`collectibles`/`collectibles-2`, `safety-equipment`/`safety-equipment-2`,
`kitchen-appliances`/`kitchen-appliances-2` (top-level/sub collision),
`jewelry-accessories`/`jewelry-accessories-2` (top-level/sub collision).

### Judgment calls worth confirming with the client

- **Automobile's 38 products → `Automotive`** (umbrella) rather than `Cars`, since the
  mix (cars, parts, accessories) is unknown. Easy to re-sort in the admin later.
- **Audio's 9 products → `Audio & Headphones`** under Electronics.
- **Wearables' 3 products → `Wearable Technology`** under Gadgets & Smart Devices.
- Old brand categories (Audi, Honda, Mitsubishi) are **dropped** — they're brands, not
  categories, and all empty.

## 4. Deployment

1. Commit `seed_final_categories.php` (server pulls from GitHub `main` via `deploy.php`).
2. Run once: `https://www.avazonia.com/seed_final_categories.php?secret=avazonia_final_structure_2026`
3. Hard-refresh the site; nav shows all 26 top-level categories; category pages list subs.
4. **Delete the seeder file from the server after the run.**

Verified locally against SQLite: 272 categories seeded, 5/5 products remapped, 0 uncategorized.