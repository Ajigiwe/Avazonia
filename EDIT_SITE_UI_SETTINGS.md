# Edit Site UI (Admin Settings) Feature

This document explains how Avazonia's "Edit Site UI" feature works so it can be reimplemented in another project.

## What It Is

An admin-only settings screen lets a store owner update UI/content knobs (brand color, announcement bar text, footer notice/address, home popup config, etc.) without deploying code.

Settings are stored in the database as simple key/value rows. The frontend reads these keys at runtime and uses them to:

- Set CSS variables (e.g. primary brand color).
- Toggle UI behaviors (e.g. product-card image slider).
- Render text content (e.g. footer address, support banner copy).
- Control shipping/deposit thresholds (used in checkout/cart logic).

## Where It Lives (Files)

- Admin UI: `admin/settings.php`
- Save API endpoint: `admin/api/save-settings.php`
- Data model: `models/Settings.php`
- Settings boot loader (read path into constants): `config/app.php`
- Common UI consumers:
  - CSS variables: `views/layout/head.php`
  - Footer/social links: `views/layout/footer.php`
  - Support banner: `views/components/support-card.php`
  - Product cards slider toggle: `views/components/product-card.php`
  - Home page (deals title/eyebrow + popup): `views/home/index.php`, `controllers/HomeController.php`
  - Deals page grid density: `views/shop/deals.php`

## Data Model

Settings are stored in a `settings` table:

- `key` (VARCHAR, primary key)
- `value` (TEXT)
- `updated_at` (timestamp, auto-updated)

Implementation: `models/Settings.php`

Key operations:

- `Settings::get($key, $default)`
- `Settings::all()` returns an associative array of `key => value`
- `Settings::set($key, $value)` upserts by primary key
- `Settings::ensureTable()` creates the table if missing

## Write Path (Admin -> DB)

1. Admin visits `admin/settings.php`.
2. The page renders many inputs whose DOM `id` is `set-<key>`.
3. Client JS collects all inputs with ids starting with `set-`:
   - It strips the `set-` prefix to produce the setting key.
   - It sends the entire map to `admin/api/save-settings.php` as JSON.
4. `admin/api/save-settings.php`:
   - Requires admin session (`Session::get('user_role') === 'admin'`).
   - Decodes JSON body.
   - Saves only keys in a backend allowlist (`$allowedKeys`).
   - Writes an audit entry to `system_logs` via `models/Logger.php`.

Important: keys not in `$allowedKeys` appear editable but will NOT persist.

## Read Path (DB -> Frontend)

There are two patterns:

### A) Boot-time constants (config/app.php)

`config/app.php` loads `Settings::all()` into `$dbSettings` and defines constants, for example:

- `PRIMARY_COLOR` from `primary_brand_color`
- `GRID_DENSITY` from `grid_density`
- `ANNOUNCEMENT_BAR` from `announcement_text`
- `FOOTER_NOTICE` from `footer_notice`
- shipping values and thresholds

Then templates use these constants directly (especially for CSS variables and global layout).

### B) Direct `$dbSettings` usage in views

Some view templates use `global $dbSettings` and read values such as:

- `footer_address`
- `instagram_link`, `facebook_link`, `tiktok_link`, etc.
- `support_title`, `support_subtitle`, `support_phone`, `support_hours`
- `product_card_slider_enabled`

## Key Settings (Partial Inventory)

The feature supports many keys; below are the most UI-relevant ones (not exhaustive):

- Branding/UI:
  - `primary_brand_color`
  - `announcement_text`
  - `footer_notice`
  - `grid_density`
  - `product_card_slider_enabled`
- Footer/Support:
  - `footer_address`
  - `support_title`, `support_subtitle`, `support_phone`, `support_hours`
- Social & SEO:
  - `instagram_link`, `facebook_link`, `tiktok_link`, `youtube_link`, `telegram_link`, etc.
  - `meta_description`, `meta_keywords`
- Home popup:
  - `home_popup_enabled`, `home_popup_type`, `home_popup_title`, `home_popup_desc`
  - `home_popup_image`, `home_popup_discount`, `home_popup_link`, `home_popup_btn_text`, `home_popup_frequency`
- Logistics knobs (not purely UI, but set in same place):
  - `preorder_deposit_pct`
  - `shipping_accra`, `shipping_kumasi`, `shipping_others`, `shipping_pickup`, `shipping_free_threshold`
  - `min_stock_threshold`

## Known Gaps / Gotchas (Very Important)

These are the things a new implementation should avoid:

1. **Admin form keys must match backend allowlist**
   - If `admin/settings.php` renders a field but the key is missing from `$allowedKeys` in `admin/api/save-settings.php`, saving silently does nothing for that key.
   - In this repo, at time of writing, `home_deals_title`, `home_deals_eyebrow`, and `product_card_slider_enabled` were present in the UI but not allowlisted, so they would not persist.

2. **Some "settings" do not affect the live system**
   - Paystack keys:
     - Admin UI includes `paystack_public_key` / `paystack_secret_key`
     - Live Paystack config is read from `.env` only via `config/paystack.php`
     - Saving these to the DB does not change checkout behavior unless the app is modified to read them from DB.
   - Currency symbol:
     - Admin UI includes `currency_symbol`
     - Many templates hardcode `₵` directly
     - Saving the symbol to DB will not update the UI unless templates are refactored to use it.

3. **Caching**
   - If your other project uses template caching, or any long-lived application process model, you must ensure changes propagate quickly (e.g., cache-bust, reload config, or fetch settings dynamically).
   - This repo includes a CSS cache-buster (`styles.css?v=time()`), but settings are otherwise read at request-time in PHP.

4. **Data types**
   - All values are stored as TEXT. Consumers must cast appropriately:
     - numbers: `(int)` / `(float)`
     - booleans: `'1'` / `'0'`
   - Be consistent in admin UI (select values, numeric inputs).

## Minimal Reimplementation Checklist (Other Project)

- Create `settings` table with `key` primary key and `value` text.
- Build an admin-only settings page with inputs named or id'd by `set-<key>`.
- Add a JSON save endpoint that:
  - Authenticates admin
  - Validates allowlisted keys
  - Upserts to the settings table
  - Logs/audits changes
- Decide on a read strategy:
  - boot-time constants for performance/simple templates
  - direct DB fetch for dynamic UI parts
- Ensure every UI control is both:
  - persisted (allowlist includes it)
  - consumed (templates/controllers actually read it)

## Suggested Improvements (Optional)

If you want to make the feature safer and easier to maintain:

- Define a single source of truth for setting definitions:
  - key name
  - label/help text
  - type (string/number/bool/color)
  - default value
  - validation rules
  - consumer(s)
- Generate both:
  - the admin form
  - the backend allowlist/validation
from that definition to prevent drift.

