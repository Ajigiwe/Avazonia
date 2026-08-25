# scripts/setup-local.ps1 - Windows one-command local setup
# Run from project root:  powershell -ExecutionPolicy Bypass -File .\scripts\setup-local.ps1
# Options:
#   -Fresh   : wipe DB and re-seed (destructive - local only)
#   -Native  : skip Docker, use native php -S (requires local MariaDB/MySQL)

param(
  [switch]$Fresh,
  [switch]$Native
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "Avazonia - local setup" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan

# 1. .env
if (-not (Test-Path ".env")) {
  if (Test-Path ".env.example") { Copy-Item ".env.example" ".env"; Write-Host "[1/5] Created .env from .env.example" -ForegroundColor Green }
  else { Write-Host "[1/5] WARNING: no .env.example found" -ForegroundColor Yellow }
} else { Write-Host "[1/5] .env exists - keeping it" -ForegroundColor Green }

# 2. php check
try { $phpVer = php -r "echo PHP_VERSION;" 2>$null } catch { $phpVer = $null }
if (-not $phpVer) { Write-Host "ERROR: php not found in PATH. Install PHP 8.1+ or use Docker." -ForegroundColor Red; exit 1 }
Write-Host "[2/5] PHP $phpVer" -ForegroundColor Green
php -m | Select-String -Pattern "pdo_mysql|gd|curl" | ForEach-Object { Write-Host "      $_" -ForegroundColor DarkGray }

# 3 and 4. DB + seed
if ($Native) {
  Write-Host "[3/5] Native mode - checking DB..." -ForegroundColor Yellow
  # Force DB_HOST to localhost for native; use SQLite if no MySQL (zero-config)
  (Get-Content ".env") -replace '^DB_HOST=.*', 'DB_HOST=127.0.0.1' | Set-Content ".env"
  (Get-Content ".env") -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000' | Set-Content ".env"
  # If DB_DRIVER not set, default to sqlite for zero-config (no MySQL needed)
  if (-not (Select-String -Path ".env" -Pattern "^DB_DRIVER")) {
    Add-Content -Path ".env" -Value "DB_DRIVER=sqlite"
    Write-Host "      Added DB_DRIVER=sqlite to .env (zero-config, no MySQL needed)" -ForegroundColor DarkGray
    Write-Host "      To use MySQL instead, remove that line and start MySQL." -ForegroundColor DarkGray
  }
  Write-Host "      Patched .env: DB_HOST=127.0.0.1, APP_URL=http://localhost:8000" -ForegroundColor DarkGray
  $freshFlag = if ($Fresh) { "--fresh" } else { "" }
  php bin/setup.php $freshFlag
  if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
  Write-Host ""
  Write-Host "Start the app:" -ForegroundColor Cyan
  Write-Host "  php -S localhost:8000 -t . router.php   (in this folder)" -ForegroundColor White
  Write-Host "  then open http://localhost:8000" -ForegroundColor White
} else {
  # Docker mode
  $dockerOk = $null; try { docker --version 2>$null; $dockerOk = $true } catch {}
  if (-not $dockerOk) {
    Write-Host "[3/5] Docker not found in PATH" -ForegroundColor Yellow
    Write-Host "      Install Docker Desktop: https://docs.docker.com/desktop/install/windows-install/" -ForegroundColor White
    Write-Host "      Or re-run with -Native if you have XAMPP/WAMP MySQL installed:" -ForegroundColor White
    Write-Host "        powershell -ExecutionPolicy Bypass -File .\scripts\setup-local.ps1 -Native" -ForegroundColor White
    Write-Host ""
    Write-Host "      Trying native setup as fallback..." -ForegroundColor Yellow
    $freshFlag = if ($Fresh) { "--fresh" } else { "" }
    # Don't fail if no DB - bin/setup.php will print helpful message
    php bin/setup.php $freshFlag
    exit 0
  }
  Write-Host "[3/5] Docker found - building and starting stack..." -ForegroundColor Green
  # Ensure .env has DB_HOST=db for Docker and remove SQLite fallback
  (Get-Content ".env") -replace '^DB_HOST=.*', 'DB_HOST=db' | Set-Content ".env"
  (Get-Content ".env") -replace '^APP_URL=.*', 'APP_URL=http://localhost:8080' | Set-Content ".env"
  if (Select-String -Path ".env" -Pattern "^DB_DRIVER") {
    (Get-Content ".env") | Where-Object { $_ -notmatch '^DB_DRIVER' } | Set-Content ".env"
    Write-Host "      Removed DB_DRIVER from .env (using MySQL in Docker)" -ForegroundColor DarkGray
  }
  Write-Host "      Patched .env: DB_HOST=db, APP_URL=http://localhost:8080" -ForegroundColor DarkGray

  docker compose up -d --build --wait
  if ($LASTEXITCODE -ne 0) { Write-Host "docker compose up failed - see output above" -ForegroundColor Red; exit 1 }

  Write-Host "[4/5] Waiting for DB to be healthy..." -ForegroundColor Green
  Start-Sleep -Seconds 5

  # Seed is applied automatically via docker/mysql/init/*.sql on first run.
  # If -Fresh was requested, we need to re-run the seed explicitly (volume already exists).
  if ($Fresh) {
    Write-Host "      -Fresh: re-seeding via bin/setup.php inside container" -ForegroundColor Yellow
    docker compose exec app php bin/setup.php --fresh
  } else {
    # Ensure tables exist even if volume was already initialized (idempotent)
    docker compose exec app php bin/setup.php
  }

  Write-Host "[5/5] Done!" -ForegroundColor Green
  Write-Host ""
  Write-Host "  App:        http://localhost:8080" -ForegroundColor White
  Write-Host "  phpMyAdmin: http://localhost:8081  (user: avazonia / avazonia, or root / root)" -ForegroundColor White
  Write-Host "  Admin:      admin@avazonia.local / Admin123!" -ForegroundColor White
  Write-Host "  Customer:   customer@avazonia.local / Admin123!" -ForegroundColor White
  Write-Host ""
  Write-Host "  Logs:   docker compose logs -f app" -ForegroundColor DarkGray
  Write-Host "  Stop:   docker compose down" -ForegroundColor DarkGray
  Write-Host "  Wipe:   docker compose down -v   (deletes DB volume)" -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "Tip: if you change .env APP_URL, restart: docker compose restart app  (or restart php -S)" -ForegroundColor DarkGray
