# Avazonia — Multi-Vendor Marketplace

Hand-rolled PHP MVC + MariaDB 10.4 + Apache. No framework, no Composer.

---

## Run locally (one command)

### Option A — Docker (recommended)

Requires [Docker Desktop](https://docs.docker.com/desktop/install/windows-install/) (Windows/Mac) or Docker Engine (Linux).

```powershell
# Windows (PowerShell)
powershell -ExecutionPolicy Bypass -File scripts\setup-local.ps1
# → App: http://localhost:8080
# → phpMyAdmin: http://localhost:8081  (avazonia / avazonia, or root / root)
```

```bash
# Mac / Linux
chmod +x scripts/setup-local.sh
./scripts/setup-local.sh
```

What it does:
1. Creates `.env` from `.env.example` if missing
2. Builds `php:8.2-apache` with `pdo_mysql`, `gd`, `rewrite`
3. Starts MariaDB 10.4 + app + phpMyAdmin via `docker-compose.yml`
4. Runs `bin/setup.php` — creates tables and seeds demo data

**Seed accounts** (password `Admin123!` for both):
- `admin@avazonia.local` — admin
- `customer@avazonia.local` — customer

**Useful commands:**

```powershell
docker compose logs -f app        # app logs
docker compose exec app bash      # shell inside app
docker compose exec app php bin/setup.php          # re-seed (idempotent)
docker compose exec app php bin/setup.php --fresh  # wipe & re-seed
docker compose down               # stop
docker compose down -v            # stop + delete DB data (fresh next up)
```

DB is persisted in volume `avazonia_db_data`. First `docker compose up` runs `docker/mysql/init/01-schema.sql` + `02-seed.sql` automatically. Subsequent starts skip init — use `bin/setup.php` to re-apply.

### Option B — Native (XAMPP / WAMP / brew)

Requires PHP 8.1+ with `pdo_mysql`, `gd`, `curl` and a local MySQL/MariaDB.

```powershell
# Windows
powershell -ExecutionPolicy Bypass -File scripts\setup-local.ps1 -Native

# Mac/Linux
./scripts/setup-local.sh --native
```

Or manually:

```bash
cp .env.example .env
# edit .env: DB_HOST=127.0.0.1, APP_URL=http://localhost:8000
php bin/setup.php
php -S localhost:8000 -t . router.php
# → http://localhost:8000
```

> `router.php` is required for `php -S` — it mimics `.htaccess` rewriting. Don't use `php -S` without it.

---

## Health check

```
GET /health       → {"status":"ok",...}
GET /health?db=1  → also checks DB + table counts (returns 500 if DB down)
```

Useful for Docker (`HEALTHCHECK`) and for verifying setup without opening the browser.

---

## Environment

| File | Purpose | Committed? |
|---|---|---|
| `.env.example` | Template for local dev (safe defaults, `MAIL_MAILER=log`) | yes |
| `.env` | Your local config (read by `config/app.php`) | **no** (gitignored) |
| `.env.production.backup` | Auto-created backup of your old production `.env` on first `setup-local.ps1` run | no |

**Key vars:**

```ini
APP_URL=http://localhost:8080   # must match the URL you open in the browser
DB_HOST=db                      # `db` inside Docker, `127.0.0.1` for native
DB_PORT=3306                    # Docker maps host 3307→3306; native uses 3306
MAIL_MAILER=log                 # local: logs to storage/logs/mail.log, no SMTP
```

Paystack test keys can be left blank locally — checkout will still create orders (payment verification will fail gracefully).

---

## Importing production data

`production_db_fixed.sql` in the repo has a known column-count mismatch (30 DDL columns vs 27 INSERT values) and will fail on import. For a fresh local DB, use `bin/setup.php` instead.

If you need production rows locally:

```bash
# 1. Start Docker DB
docker compose up -d db --wait

# 2. Export production correctly, or fix the file:
#    - Use production_db.sql (valid) + migrations/add_currency.sql, or
#    - Import via phpMyAdmin with "column names" patch.

# 3. Then run setup to patch missing tables:
docker compose exec app php bin/setup.php
```

---

## Project structure

```
index.php            Front controller + route table (45 routes)
router.php           Built-in server router (for php -S)
config/              app.php (env + constants), database.php (PDO), paystack.php
core/                Router, Controller, Model, Session, Mailer
controllers/         11 storefront controllers + HealthController
models/              15 models (Product, Order, User, Category, etc.) — raw SQL
views/               Raw PHP templates (layout, shop, product, cart, checkout, ...)
admin/               16 page-per-file admin pages (outside router, literal URLs)
api/                 7 standalone endpoints (paystack-verify, track, newsletter, ...)
public/              css, uploads/products|videos|sliders|categories
docker/              Dockerfile, php/local.ini, entrypoint.sh, mysql/init/*.sql
bin/setup.php        Idempotent setup & seed runner
scripts/             setup-local.ps1 / setup-local.sh (one-command bootstrap)
storage/logs/        Local mail.log + app logs (gitignored)
```

---

## Troubleshooting

**`DB connection failed` or `getaddrinfo for db failed`**
- Inside Docker: `DB_HOST` must be `db`. Run `scripts/setup-local.ps1` again (it patches `.env`).
- Native `php -S`: set `DB_HOST=127.0.0.1` and `APP_URL=http://localhost:8000` in `.env`.

**`SQLSTATE[HY000] [2002] Connection refused` on host**
- MariaDB not running. Docker: `docker compose up -d db --wait` then `docker compose logs db`. Native: start XAMPP/MAMP MySQL.

**Port already in use (`8080` / `3307` / `8081`)**
- Change the host port in `docker-compose.yml` (e.g. `"8082:80"`) or stop the conflicting service.

**Images/videos 404 or uploads fail**
- Ensure `public/uploads/{products,categories,sliders,videos}` exists and is writable. `docker/entrypoint.sh` and `bin/setup.php` create them. On native Windows, no `chmod` needed.

**Emails not sending locally**
- Expected: `MAIL_MAILER=log` writes to `storage/logs/mail.log` and `error_log`. Set `MAIL_MAILER=brevo` + `BREVO_API_KEY` to send for real.

**`/health` works but `/` is 500**
- Check `storage/logs`, `env_debug.log`, and `docker compose logs -f app`. Usually a missing table — run `php bin/setup.php`.

---

## Production notes

- Do **not** commit `.env`. Use `prod.env.example` as reference.
- Delete or gate `deploy.php`, `diagnose.php`, `check_*.php` before exposing to the internet (they run `git reset --hard` or dump DB with no auth).
- Set `PAYSTACK_PUBLIC_KEY`/`_SECRET_KEY` to live keys and `APPL_URL=https://www.avazonia.com` in production `.env`.
- `docker-compose.yml` is for local dev only — production uses Apache + MariaDB on the host (cPanel / VPS).
