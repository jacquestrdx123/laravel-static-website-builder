#!/usr/bin/env bash
# Idempotent Ubuntu bootstrap for SiteForge (single VPS: Caddy + Nginx + PHP-FPM).
#
# Usage (as root):
#   export APP_HOST=app.example.com
#   export SITES_DOMAIN=sites.example.com
#   export CADDY_EMAIL=admin@example.com
#   export DEPLOY_USER=deploy   # optional, default deploy
#   export PHP_VERSION=8.3      # optional
#   bash deploy/bootstrap-vps.sh
#
# Run from the repo root (or set REPO_ROOT).

set -euo pipefail

APP_HOST="${APP_HOST:?Set APP_HOST (e.g. app.example.com)}"
SITES_DOMAIN="${SITES_DOMAIN:?Set SITES_DOMAIN (e.g. sites.example.com)}"
CADDY_EMAIL="${CADDY_EMAIL:?Set CADDY_EMAIL}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.3}"
APP_ROOT="${APP_ROOT:-/var/www/builder/current/public}"
SITES_ROOT="${SITES_ROOT:-/srv/websites}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php${PHP_VERSION}-fpm.sock}"

REPO_ROOT="${REPO_ROOT:-$(cd "$(dirname "$0")/.." && pwd)}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root (sudo -E bash deploy/bootstrap-vps.sh)" >&2
  exit 1
fi

echo "==> Installing packages (nginx, php${PHP_VERSION}, redis, supervisor, curl, unzip, git)"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y \
  nginx \
  "php${PHP_VERSION}-fpm" \
  "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-sqlite3" \
  "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-zip" \
  "php${PHP_VERSION}-gd" \
  "php${PHP_VERSION}-bcmath" \
  "php${PHP_VERSION}-intl" \
  "php${PHP_VERSION}-redis" \
  redis-server \
  supervisor \
  curl \
  unzip \
  git \
  ufw

echo "==> Installing Caddy"
if ! command -v caddy >/dev/null 2>&1; then
  apt-get install -y debian-keyring debian-archive-keyring apt-transport-https
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
    | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
    | tee /etc/apt/sources.list.d/caddy-stable.list >/dev/null
  apt-get update -y
  apt-get install -y caddy
fi

echo "==> Creating deploy user and directories"
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos '' "$DEPLOY_USER"
fi
usermod -aG www-data "$DEPLOY_USER"

mkdir -p /var/www/builder
mkdir -p "$SITES_ROOT/domains"
chown -R "$DEPLOY_USER:www-data" /var/www/builder
chown -R www-data:www-data "$SITES_ROOT"
chmod 2775 "$SITES_ROOT" "$SITES_ROOT/domains"

echo "==> Writing Caddy edge config"
sed \
  -e "s/CADDY_EMAIL/${CADDY_EMAIL}/g" \
  -e "s/APP_HOST/${APP_HOST}/g" \
  "$REPO_ROOT/deploy/caddy.edge.Caddyfile" >/etc/caddy/Caddyfile

echo "==> Writing Nginx origin config"
# Escape dots in SITES_DOMAIN for the nginx regex server_name
SITES_DOMAIN_REGEX="${SITES_DOMAIN//./\\.}"
sed \
  -e "s|APP_HOST|${APP_HOST}|g" \
  -e "s|SITES_DOMAIN|${SITES_DOMAIN_REGEX}|g" \
  -e "s|PHP_FPM_SOCK|${PHP_FPM_SOCK}|g" \
  -e "s|APP_ROOT|${APP_ROOT}|g" \
  -e "s|SITES_ROOT|${SITES_ROOT}|g" \
  "$REPO_ROOT/deploy/nginx.origin.conf" >/etc/nginx/sites-available/siteforge-origin

ln -sfn /etc/nginx/sites-available/siteforge-origin /etc/nginx/sites-enabled/siteforge-origin
rm -f /etc/nginx/sites-enabled/default

# Ensure Nginx only needs this site; main nginx.conf should include sites-enabled.
if ! grep -q 'listen 127.0.0.1:8080' /etc/nginx/sites-available/siteforge-origin; then
  echo "Origin config missing loopback listen" >&2
  exit 1
fi

echo "==> Firewall (allow 22, 80, 443)"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable || true

echo "==> Installing Supervisor programs"
sed \
  -e "s|APP_BASE|/var/www/builder/current|g" \
  -e "s|DEPLOY_USER|${DEPLOY_USER}|g" \
  -e "s|PHP_BIN|/usr/bin/php|g" \
  "$REPO_ROOT/deploy/supervisor/siteforge.conf" >/etc/supervisor/conf.d/siteforge.conf

echo "==> Validating and reloading services"
nginx -t
systemctl enable nginx caddy redis-server supervisor "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"
systemctl reload nginx || systemctl restart nginx
systemctl reload caddy || systemctl restart caddy
supervisorctl reread || true
supervisorctl update || true

echo ""
echo "Bootstrap complete."
echo "Next:"
echo "  1. Put a release at /var/www/builder/current (git clone + composer)."
echo "  2. Configure .env with APP_URL=https://${APP_HOST} SITES_DOMAIN=${SITES_DOMAIN} SITES_PUBLISH_PATH=${SITES_ROOT} HOSTING_DRIVER=filesystem"
echo "  3. php artisan key:generate && php artisan migrate --force && php artisan storage:link && php artisan optimize"
echo "  4. supervisorctl start all"
echo "  5. Smoke-test: docs/hosting/caddy-nginx.md"
