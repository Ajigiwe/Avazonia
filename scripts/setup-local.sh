#!/usr/bin/env bash
# scripts/setup-local.sh â€” Mac/Linux one-command local setup
# Usage:  chmod +x scripts/setup-local.sh && ./scripts/setup-local.sh
#         ./scripts/setup-local.sh --fresh   (wipe & re-seed)
#         ./scripts/setup-local.sh --native  (no Docker, use local MySQL + php -S)
set -e
cd "$(dirname "$0")/.."

FRESH=""
NATIVE=""
for arg in "$@"; do case $arg in --fresh) FRESH="--fresh";; --native) NATIVE=1;; esac; done

echo "Avazonia â€” local setup"
echo "======================"

if [ ! -f .env ]; then
  if [ -f .env.example ]; then cp .env.example .env; echo "[1/5] Created .env from .env.example"
  else echo "[1/5] WARNING: no .env.example"; fi
else echo "[1/5] .env exists â€” keeping it"; fi

if ! command -v php >/dev/null 2>&1; then echo "ERROR: php not found. Install PHP 8.1+."; exit 1; fi
echo "[2/5] PHP $(php -r 'echo PHP_VERSION;')"
php -m | grep -i -E 'pdo_mysql|gd|curl' | sed 's/^/      /' || true

if [ -n "$NATIVE" ]; then
  echo "[3/5] Native mode â€” expecting MySQL on localhost:3306"
  sed -i.bak 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env 2>/dev/null || sed -i '' 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
  sed -i.bak 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env 2>/dev/null || sed -i '' 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env
  echo "      Patched .env: DB_HOST=127.0.0.1, APP_URL=http://localhost:8000"
  php bin/setup.php $FRESH
  echo ""
  echo "Start: php -S localhost:8000 -t .  -> http://localhost:8000"
  exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "[3/5] Docker not found. Install Docker Desktop or run with --native"
  echo "      ./scripts/setup-local.sh --native"
  php bin/setup.php $FRESH || true
  exit 0
fi

echo "[3/5] Docker â€” building & starting stack..."
sed -i.bak 's/^DB_HOST=.*/DB_HOST=db/' .env 2>/dev/null || sed -i '' 's/^DB_HOST=.*/DB_HOST=db/' .env
sed -i.bak 's|^APP_URL=.*|APP_URL=http://localhost:8080|' .env 2>/dev/null || sed -i '' 's|^APP_URL=.*|APP_URL=http://localhost:8080|' .env
echo "      Patched .env: DB_HOST=db, APP_URL=http://localhost:8080"

docker compose up -d --build --wait

echo "[4/5] Waiting for DB..."
sleep 5

if [ -n "$FRESH" ]; then
  echo "      --fresh: re-seeding"
  docker compose exec app php bin/setup.php --fresh
else
  docker compose exec app php bin/setup.php
fi

echo "[5/5] Done"
echo ""
echo "  App:        http://localhost:8080"
echo "  phpMyAdmin: http://localhost:8081  (avazonia/avazonia or root/root)"
echo "  Admin:      admin@avazonia.local / Admin123!"
echo "  Customer:   customer@avazonia.local / Admin123!"
echo ""
echo "  Logs: docker compose logs -f app"
echo "  Stop: docker compose down"
echo "  Wipe: docker compose down -v"
