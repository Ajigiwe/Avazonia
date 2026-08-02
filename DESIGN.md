# Avazonia — UI/UX & Functionality Clone Specification

**Purpose:** A complete reference to recreate the Avazonia e-commerce platform (storefront, account area, checkout flow, and admin panel) including every visual detail and functional behavior.

**Stack:** Vanilla PHP + MySQL, no front-end framework. Server-rendered HTML with progressive JS enhancement. All dynamic UI values (colors, copy, toggles) are driven by a `settings` key/value table.

**Brand:** Premium tech & gadgets retailer. Ghana market, currency **GHS (₵)**. Payments via **Paystack** (Cards + Mobile Money: MTN / Telecel / AT) and **Bank Transfer**. Tone: bold, editorial, "drop" culture language ("THE DROP", "DEPLOY NEW DROP", "Unified Intelligence Engine").

---

## 1. Design Tokens (canonical — from `public/css/styles.css`)

### 1.1 Color System

| Token | Hex | Usage |
|---|---|---|
| `--ink` | `#0D0D0D` | Primary text, dark surfaces, buttons |
| `--paper` | `#FFFFFF` | Page background, cards |
| `--red` | `#E8002D` | Brand primary / CTA / sale tags / errors |
| `--red-deep` | `#B8001F` | Hover/active state of brand red |
| `--off` | `#F4F1EC` | Off-white section bands, page tint |
| `--light-gray` | `#E8E5DF` | Borders, dividers, disabled fills |
| `--mid-gray` | `#55514E` | Secondary/muted text, labels |

Derived/inline colors used throughout:
- Links & small labels: red with hover to `--red-deep`.
- Trend indicators: up `#00a854`, down `--red`.
- Admin status badges + email status chips use the order-status palette (see §7.4).

### 1.2 Typography

- `--f-display` / `--f-body` / `--f-semi`: **Plus Jakarta Sans** (400/500/600/700/800).
- `--f-mono`: **Outfit** (used for micro-labels, eyebrows, kickers, meta — uppercase, small, letter-spaced).
- Display headings: heavy weight (800–900), tight `letter-spacing: -0.02em` to `-0.04em`, tight line-height (0.9–1.05). Hero / page titles often use `clamp()` fluid sizing (e.g. `clamp(38px, 8vw, 64px)`).
- Eyebrow/label convention: mono font, `10–11px`, `text-transform: uppercase`, `letter-spacing: 0.08–0.1em`, `--mid-gray`.

### 1.3 Motion

- `--ease: cubic-bezier(.25,.46,.45,.94)` — the single transition easing for hovers/fades.
- Standard transitions: `transition: all var(--ease) 0.3s` (buttons, cards, nav).
- Page/fade-in keyframes on load (fade + slight translate up).
- No box-shadow on storefront surfaces by default — bold 2px ink borders instead (admin panels DO use shadows).

### 1.4 Layout Patterns (grid widths)

| Pattern | Rule |
|---|---|
| Shop sidebar + content | `grid-template-columns: 1fr 380px` (sidebar right) |
| Cart | content `1fr` + summary `380px` |
| Checkout | form `1fr` + summary `360px` |
| Account | sidebar `240px` + content `1fr` |
| Home category grid | hero tile (span 2 cols) + 6 small tiles |
| Admin dashboard | `repeat(4, 1fr)` stats → `1.5fr/1fr` content |

Breakpoints roughly at 1200px, 1024px, 900px (admin), 768px, 600px. Storefront grid collapses to 1 column on mobile.

### 1.5 Admin Tokens (from admin layout CSS)

- `--sidebar-w: 260px`
- `--admin-bg: #F9FAFB`
- Admin font stack: **Outfit** (800/900 — headings/buttons), **Inter** (400–800 — body), **JetBrains Mono** (700 — refs/codes/timestamps).
- Sidebar: `--ink` background, white text, active item = red background block with soft red glow (`box-shadow: 0 0 20px rgba(...) red`), rounded corners on active pill. Off-canvas (slide over) below 900px.
- Admin buttons: square (`border-radius: 0`), full-weight 900, uppercase, ink or outline-in-ink.

---

## 2. Core Components

### 2.1 Buttons
- **`.btn-ink`** — black (`--ink`) background, white text, uppercase, bold, pill or square (admin overrides to square), hover darkens/slightly scales.
- **`.btn-red`** — brand red background, white text, primary CTA. Hover `--red-deep`.
- **`.btn-outline`** — transparent, 2px `--ink` border, ink text; hover fills ink.
- Sizes: default ~48px tall; small variants for cards.
- Admin "DEPLOY NEW DROP" style: full-width, `height: 50px`, weight 900, square, ink fill or ink outline variant.

### 2.2 Form Elements
- Inputs: 1px `--light-gray` border, generous padding (~14px 16px), 15px text; focus border ink; error state red border + red message.
- Checkbox/radio: custom square/circle controls, red when checked.
- Labels: mono eyebrow style or 600-weight small caps.
- Rows stacked vertically, labels above fields. Form section blocks separated with light dividers.

### 2.3 Badges / Tags
- Storefront: product tags — `NEW`, `HOT`, `DEAL`, `PRE-ORDER` (red/ink pills, small, uppercase mono). Sale percent tag red.
- Order status badge (storefront + admin): colored pill — see §7.4 palette.
- Rating stars: ★ filled red/ink; numeric score beside.

### 2.4 Product Card (`product-card`)
- Image area with ratio box; optional image slider dots (toggle via `product_card_slider_enabled` setting).
- Name (600, 15–16px), mono meta line (SKU/category), price row — current price 800-weight; old price struck-through `--mid-gray`; "from" prefix for variable price.
- Tag pill top-left; wishlist heart top-right (requires login).
- "Action arrow" (→) that slides the card / reveals quick actions on hover.
- Hover: lift + border color red. Click → product detail.

### 2.5 Support Card (`support-card`)
- Tint band (`--off`) card with mono eyebrow, heading, description, mono CTA link → answers live chat / WhatsApp / support route. Variants themed by icon.

### 2.6 Share Modal (`share-modal`)
- Modal overlay; native **Web Share API** on mobile; fallback shows WhatsApp + copy-link buttons with URL textbox; red/ink styling; close on overlay click / ✕.

### 2.7 Admin Panel (`panel`)
- White card, subtle shadow, header row with `.panel-title` (900 weight) + optional action link (e.g. "Full Ledger →" red mono).
- Tables: `.admin-table` — uppercase 11px gray headers, row separators `--light-gray`, hover row tint; mono refs; status badges.
- Buttons styled square ink; danger actions red.
- Toast notifications: fixed top-right, auto-dismiss; admin polls `/admin/api/notifications.php?action=list` every **30s** and shows unread toasts linking to `view-order.php?id=`.

---

## 3. Storefront Shell

### 3.1 Layout (`head`, `nav`, `hero`, `footer`)
- `<head>`: SEO title/meta, OG + Twitter cards, favicon, Google Fonts (Plus Jakarta Sans + Outfit), CSS vars, gtag `G-G3GWGCPMPP`, dynamic CSS var overrides injected from settings.
- **Nav:** left = logo/wordmark "Avazonia." (red dot period), center = category dropdown links (from `categories` table), right = search (with live suggestions), wishlist heart (count badge), account, cart icon with item-count badge. Sticky top. Mobile: hamburger → slide-in menu + fixed bottom nav (Home / Shop / Deals / Account).
- **Search:** input opens dropdown of matching product suggestions from `/api/search-suggestions` (image + name + price, red "View" CTA, "No matches" empty state).
- **Hero (per-page):** `.hero` band with `.template-split` (text left, image right) or full-width image template; dotted slider nav; page-matched copy/eyebrow; driven by `hero` settings. On `template-split`, text + floating image; CTA buttons (red primary + ink/ghost).
- **Footer:** ink background, 4 pillars — **Identity** (logo + tagline), **Company** (about, contact, FAQ…), **Support** (shipping, returns, warranty, track-order…), **Newsletter** (email input + subscribe button). Bottom bar: copyright "© YEAR Avazonia — Crafted in Takoradi, Ghana", payment icons, legal links.

### 3.2 Settings-driven UI (clone-critical)
The admin "Edit Site UI" page writes to a `settings` table consumed at render time. Re-implement as a key/value store with typed getters.

Key settings observed:
- Branding: site name, logo, tagline, primary color (injects CSS vars / email color), footer text.
- Home: hero title/subtitle/CTAs, popup (welcome popup enable + content), content blocks, featured section toggle.
- Shop: `product_card_slider_enabled` (image slider toggle), items per page (24 default).
- Deals: `deals_grid_title`, section settings, preorder deposit.
- Checkout: `preorder_deposit_pct` (default 5) — % deposit for pre-orders.
- Social/contact: phone, WhatsApp number, email, address, socials (used by footer + emails).
- Infrastructure: `home_page_active` / maintenance-mode flag, Paystack keys, shipping.

---

## 4. Storefront Pages — UI/UX + Behavior

### 4.1 Home (`/`)
- Hero slider (per §3.1).
- **Category grid:** 1 large editorial tile (2 cols) + 6 tiles; each = image + name, hover: image zoom + arrow. Tiles pull from `categories`.
- **Featured products:** "Featured" section header (eyebrow + 800 display title), responsive grid of product cards.
- **Deals strip:** red-banded horizontal strip with countdown/CTA to `/deals` (configurable via settings).
- **Support band:** stacked support cards (whatsapp/shipping/returns).
- **Newsletter popup:** modal on entry (once per session) — email capture → `/api/newsletter` or equivalent; success inline.
- Content sections pulled from settings (text blocks w/ headings + copy).

### 4.2 Shop / Catalog (`/shop`)
- Header block: eyebrow + big display title ("THE DROP"), description, item count.
- Sidebar (right, 380px) filters: categories (checkboxes, links to `?cat=`), sort options, price bands, clear-all.
- Grid: 24 product cards per page (`?page=`) + pagination (numbered + prev/next arrows, active page ink, red hover).
- Special category slugs: `deals-offers`, `new-arrivals`, `top-selling` → alternate grids/tabs.
- Search via `?q=`; empty state with red CTA back to shop.
- Filter/sort state persists in URL for shareability.

### 4.3 Deals (`/deals`)
- Tabs: **Deals / Pre-Orders / Drop Shipping** (pill tabs, active = ink fill).
- Deals grid from settings-driven items; each deal card shows red percent tag, old price struck.
- Pre-orders: cards tagged `PRE-ORDER`, deposit amount shown ("Pay X% deposit"), ships-on date.
- Drop-shipping tab: explainer copy + CTA cards.

### 4.4 Product Detail (`/product/{slug}`)
- JSON-LD product schema.
- **Gallery:** main image + thumbnails; hover/click swaps; wishlist heart; share button (opens share-modal); stock indicator.
- **Buy box:** category/eyebrow, 800 title, rating + review count (anchor to reviews), price row (sale strikethrough / "from"), variant selectors (color/size pills — selected = ink border), qty stepper, **Add to Cart** (red) + **Buy Now**.
- Pre-order items: deposit toggle → shows deposit price + "Pay Deposit" CTA.
- Trust row: shipping promise / returns / warranty mono microcopy with icons.
- **Tabs:** Description (rich text) / Specs (dl table) / Reviews.
- **Reviews:** list (name, stars, date, comment, verified badge), average summary; form (rating + text) → POST `/api/review-add` → redirect `?msg=review_success#reviews`. Login required; "Login to review" CTA if guest.
- Related products strip.

### 4.5 Cart (`/cart`)
- Step header: 1. Cart → 2. Checkout → 3. Payment (current = ink, done = red check).
- Layout `1fr 380px`.
- Line items: image, name, variant label, unit price, qty stepper, line total, remove ✕.
- Summary card: subtotal, shipping (free-threshold message if free over X), total 800; promo code input (if enabled); **Proceed to Checkout** (ink, full-width).
- Empty cart state: illustration/emoji, "Your cart is empty" + red **Start Shopping** CTA.
- Quantity updates via POST `/cart/update`; remove via `/cart/remove`; all AJAX-friendly, page refreshes totals.

### 4.6 Checkout (`/checkout`)
- Sticky progress bar: `.pb-step` with `.on` (current) and `.done` (passed) states — numbered circles + labels.
- Layout form `1fr` + summary `360px`.
- **Forms:** contact (name, email, phone), shipping (address, city, region dropdown, landmark/notes), optional billing-same-as-shipping.
- **Payment:** Paystack (cards) + Bank Transfer radio; if Pre-order items present → deposit radio with `preorder_deposit_pct` shown as "Pay X% (₵Y) now" + "Pay full amount".
- Order summary sticky: line items, subtotal, shipping, total (or deposit total).
- Submit → POST `/checkout` → Paystack initialize or Bank-Transfer instructions screen → `/checkout/success`.

### 4.7 Order Success (`/checkout/success`)
- Animated check (CSS draw), "Order Confirmed", big order ref (`NX-000000` style), amount, payment channel, "What happens next" list, buttons: **View Invoice** / **Track Order** / **Continue Shopping**.

### 4.8 Order Invoice (`/order/invoice/{ref}`)
- Standalone print-friendly page: brand header, invoice no/date, bill-to, line-item table (item / qty / price / total), totals, payment status badge, footer terms + "Thank you" note. Print CSS hides nav/chrome.

### 4.9 Account Area (`/account/*`) — shell `240px 1fr`
Sidebar (sticky): Overview, Orders, Wishlist, Settings, Logout (red). Mobile: collapsible top accordion.
- **Overview** `/account`: greeting, mini stats (orders count, total spent), recent orders list with status, quick links.
- **Orders** `/account/orders`: list of cards (ref, date, items thumbnail, total, status badge, reorder button).
- **Order Details** `/account/order/{id}`: status timeline (pending → processing → shipped → delivered), line items, totals, address, cancel button (if cancellable), support contact.
- **Wishlist** `/account/wishlist`: grid of product cards (wishlist variant: remove + add-to-cart), empty state.
- **Settings** `/account/settings`: profile form (name, email, phone, avatar), password change block (current/new/confirm), notification prefs.

### 4.10 Auth Pages
- **Login** `/login`: brand panel + form; email + password; red error box; "Remember me"; links: Forgot password, Create account; **successful admin login → redirect `/admin`**, customer → `/account`. Redirect-back preserved (`?redirect=`).
- **Register** `/register`: full name, email, phone, password + confirm; terms checkbox; success → verify-email prompt.
- **Verify Pending** `/verify-email`: "Check your inbox" state, resend link.
- **Forgot Password** `/forgot-password`: email → success notice "link sent".
- **Reset Password** `/reset-password`: token-gated new password form.
- Design: split/auth card centered, ink/off background, red primary CTA; mono eyebrows.

### 4.11 Static / Info Pages
- **About** `/about`: story blocks, values grid, image bands, stats counters.
- **Contact** `/contact`: contact info cards + **red form box** (name, email, subject, message) → success toast; admin notified by email (contact_admin_notify).
- **FAQ** `/faq`: accordion (plus/minus icons), category grouping, support CTA at bottom.
- **Track Order** `/track-order`: order ref + email/phone lookup → AJAX timeline (milestones with check/dot states, timestamps) + order summary; not-found error state.
- **Shipping / Returns / Warranty / Terms / Privacy / Payment Policy:** editorial content pages — headline block + prose, mono eyebrow headers, info cards where relevant (e.g. shipping zones table with fees + ETA, returns window, warranty coverage).

---

## 5. Functional Behaviors (back-end behaviors to reimplement)

1. **Routing** — single entry `index.php` + `core/Router.php`: REST-ish paths + POST actions + API JSON endpoints; slugs from DB; 404 page styled to brand.
2. **Auth** — sessions; password_hash; email verification (24h link); password reset (1h token); role flag `user_role` (admin → `/admin`).
3. **Cart** — DB-backed per user + guest via session merge; API add (`/api/cart-add`), update, remove; stock validation.
4. **Search suggestions** — `/api/search-suggestions?q=` → top N matching products (image, name, price) for nav dropdown.
5. **Wishlist** — toggle add/remove (login required), count badge in nav.
6. **Reviews** — star rating + text; verified badge; `/api/review-add`; average rating recompute on product.
7. **Checkout** — validates stock + address; Paystack initialize → verify webhook/callback → mark paid; Bank Transfer shows instructions; **pre-orders** charge deposit `preorder_deposit_pct`; order ref format `NX-######`; emails fired on placed/paid/shipped/cancelled/refunded/status-update.
8. **Order lifecycle** — statuses: `pending → paid → processing → shipped → arrived → delivered` (+ `cancelled`, `refunded`, `failed`, `approved`), each transition sends order-status email.
9. **Tracking** — public lookup by `order_ref` + identity (email/phone) → timeline from status + created/updated timestamps.
10. **Newsletter** — popup capture + footer subscribe → welcome email + admin notify.
11. **Contact form** — saves + emails admin; reply-to customer.
12. **Settings engine** — `Settings::get('key', $default)`, `Settings::all()`, `Settings::set()`, table ensure on boot; consumed everywhere for branding/feature toggles.
13. **Maintenance mode** — when on, storefront shows branded hold screen; admins bypass.
14. **Admin notifications** — created on new orders/contacts/newsletter signups; toast polling every 30s.

### 5.1 Route Map (from `index.php`)
`/` Home · `/shop` · `/deals` · `/product/{slug}` · `/cart` · `/cart/update` (POST) · `/cart/remove` (POST) · `/api/cart-add` · `/api/search-suggestions` · `/api/review-add` · `/checkout` · `/checkout/success` · `/checkout/complete` · `/checkout/init-balance` · `/order/invoice/{ref}` · `/about` · `/shipping` · `/warranty` · `/returns` · `/faq` · `/terms` · `/privacy` · `/payment-policy` · `/track-order` · `/contact` · `/login` · `/register` · `/forgot-password` · `/reset-password` · `/verify-email` · `/account` (+ sub-routes)

---

## 6. Admin Panel — UI/UX + Behavior

**Shell:** fixed sidebar (260px, ink, logo top, nav items, active = red block + glow, logout bottom) + top header (page title area, admin avatar, notification bell with unread count) + content on `--admin-bg`. Off-canvas sidebar below 900px (hamburger). Admin font stack §1.5.

**Nav sections:** Dashboard · Products · Categories · Brands · Orders · Users · Settings · Sliders · Maintenance · Newsletter · Logs.

### 6.1 Dashboard (`/admin`)
- Header: big 800 display title "Performance Insights" + mono eyebrow "Unified Intelligence Engine • Active Tracking".
- **Stat cards** (4-col): Total Revenue (₵, MoM ▲/▼ %), Total Orders (MoM %), Avg Order Value, Monthly Revenue Goal (ink card with green progress bar + "84%").
- **Revenue Trends** line chart (Chart.js, last 14 days, black line, grey grid).
- **Sales by Category** doughnut (Chart.js, greyscale palette, 70% cutout).
- **Recent Transactions** table: Ref (mono) / Customer (name+email stacked) / Amount (800 ₵) / Status badge; "Full Ledger →" red link.
- **Strategic Actions:** "DEPLOY NEW DROP" (→ add-product) + "INVENTORY CONTROL" (→ products) — full-width square ink buttons.

### 6.2 Products (`/admin/products.php`, `add-product.php`, `edit-product.php`)
- Listing: table or card grid — image thumb, name, SKU, category, price, stock, status (active/draft), actions (edit / duplicate / delete). Search + filter + pagination.
- Form: name, slug, SKU, description (rich), spec table, images (upload/URL, set main), gallery, category + brand selects, price, sale price, cost, stock qty, pre-order flag + ship date, tags (NEW/HOT/DEAL), featured toggle, active toggle. Save → toast + list refresh.

### 6.3 Categories / Brands
- List with sortable rows (name, slug, image, product count, status), add/edit modal or inline form, delete guard when products attached.

### 6.4 Orders (`/admin/orders.php`, `view-order.php`)
- Orders table: ref, customer, total, status filter pills + search; click → detail.
- **View order:** header (ref + status badge + timestamps), customer info + address card, payment card (method, channel, amounts, transaction ref), line items table, totals, **status changer** (dropdown/buttons per transition incl. paid/processing/shipped/arrived/delivered/cancelled/refunded) → fires status-update email + notification.

### 6.5 Users (`/admin/users.php`)
- Users table (name, email, phone, role, joined, orders count, status), toggle role/customer vs admin, suspend, view user.

### 6.6 Settings (`/admin/settings.php`) — "Edit Site UI"
- Tabbed/grouped settings form (Branding / Home / Shop & Deals / Checkout & Payments / Contact / Social / Integrations / Shipping / General).
- Branding: site name, logo URL, tagline, primary color picker.
- Home: hero title, subtitle, CTAs, popup toggle + content, featured toggle, content blocks (title + body).
- Shop: `product_card_slider_enabled`, items-per-page, deals title, preorder deposit %.
- Save → POST `/admin/api/save-settings.php` → per-key upsert → success toast.
- Any key/value; admin can add custom keys.

### 6.7 Sliders (`/admin/sliders.php`)
- Manage hero slides: order, template (`template-split` / full), title, subtitle, image, CTA link/text, active toggle; reorder.

### 6.8 Maintenance (`/admin/maintenance.php`)
- Toggle maintenance mode; message editor; preview of hold screen.

### 6.9 Newsletter (`/admin/newsletter.php`)
- Subscribers table (email, date, source), export CSV, remove; optional broadcast composer (subject + body → send to all).

### 6.10 Logs (`/admin/logs.php`)
- Activity/error log table: timestamp (JetBrains Mono), level, message, context; filter by level; clear.

### 6.11 Admin APIs
- `admin/api/save-settings.php` (upsert settings)
- `admin/api/notifications.php?action=list` (toast polling, 30s)
- Product/category/brand/order/user CRUD endpoints (POST actions)
- `admin/api/delete-*.php` style deleters with confirm.

---

## 7. Order Status Palette & Email System

### 7.1 Status → Badge Color
| Status | Color |
|---|---|
| pending | `#9ca3af` |
| paid | `#0ea5e9` |
| processing | `#f59e0b` |
| shipped | `#8b5cf6` |
| arrived | `#22c55e` |
| delivered | `#16a34a` |
| approved | `#00a854` |
| paid-full | `#10b981` |
| cancelled | `#ef4444` |
| refunded | `#7c2d12` |
| failed | `#9ca3af` (destructive variant) |

### 7.2 Email System (`emails/`)
- Shared shell `emails/layout.php`: 600px centered card, ink header with wordmark, **primary color hero band** (red by default; green for paid/shipped; blue for shipped/contact; grey for cancelled; amber for refunded; gradient backgrounds), order table, status chips, `info-block` (accent left border), `notice` (amber callout), rounded 50px buttons, footer "© YEAR {name} — Crafted in Takoradi, Ghana" + Shop / My Account / Support + recipient line. Responsive <600px full-width buttons.
- Templates: `order_placed` (green-check hero, item table, delivery info block), `order_paid`, `order_shipped` (delivery notice), `order_cancelled` (WhatsApp support CTA), `order_refunded` (3–5 business day notice, payment method), `order_status_update` (dynamic status color/icon/msg map), `verification` (24h link + fallback URL block), `password_reset` (1h link + fallback URL), `contact_admin_notify`, `newsletter_welcome`, `newsletter_admin_notify`.
- Buttons route to `/account` (View My Orders), `/account` (Track), WhatsApp (`https://wa.me/{WHATSAPP_NUMBER}`), mailto reply.

---

## 8. Mobile Behavior (storefront)

- Sticky bottom nav (Home / Shop / Deals / Account) with red active state.
- Hamburger → full/slide-in menu with categories + account links.
- Hero stacks (text above image); category grid 2-col then 1-col.
- Product cards remain 2-col down to small widths.
- Cart/checkout/account layouts collapse to single column; summary stacks below.
- Tables become stacked cards on admin below ~900px; sidebar off-canvas.
- Share uses native Web Share API.

---

## 9. Implementation Checklist (clone order)

1. Settings table + `Settings` helper + boot loader; define all keys.
2. CSS vars + base styles (tokens §1) + component styles (§2).
3. Storefront shell: head, nav (search suggestions), hero, footer, mobile nav.
4. Home (hero slider, category grid, featured, deals strip, popup).
5. Shop + Deals + grid + pagination + filters.
6. Product detail (gallery, variants, tabs, reviews, share).
7. Cart + checkout + success + invoice (statuses, deposit logic).
8. Auth + account area + wishlist.
9. Static pages + track-order + contact + FAQ.
10. Email templates + triggers.
11. Admin shell + all CRUD + dashboard + settings UI + notifications.
12. Wire all routes per §5.1; test full order lifecycle + Paystack webhook + deposit flow.
