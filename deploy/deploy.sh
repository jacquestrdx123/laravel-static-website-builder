#!/usr/bin/env bash
# SiteForge release deploy (no Laravel Forge).
#
# Expects a simple layout:
#   /var/www/builder/current  → active release (git working tree or symlink)
#
# Usage (as deploy user):
#   cd /var/www/builder/current && bash deploy/deploy.sh
#
# Optional env:
#   SKIP_MIGRATE=1
#   SKIP_HORIZON_RESTART=1

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

cd "$APP_DIR"

echo "==> App directory: $APP_DIR"

if [[ -d .git ]]; then
  echo "==> git pull"
  git pull --ff-only
fi

echo "==> composer install"
$COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Laravel optimize"
$PHP_BIN artisan optimize
$PHP_BIN artisan storage:link || true

if [[ "${SKIP_MIGRATE:-0}" != "1" ]]; then
  echo "==> migrate"
  $PHP_BIN artisan migrate --force
fi

if [[ "${SKIP_HORIZON_RESTART:-0}" != "1" ]]; then
  echo "==> restart queue workers (supervisor)"
  if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart siteforge-horizon siteforge-scheduler || \
      supervisorctl restart siteforge-horizon siteforge-scheduler || true
  else
    $PHP_BIN artisan horizon:terminate || true
  fi
fi

echo "Deploy finished."
