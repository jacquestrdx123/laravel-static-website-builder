#!/usr/bin/env bash
#
# SiteForge — idempotent server provisioning (no Laravel Forge).
#
# Brings a bare Ubuntu 24.04 box to the state deploy/deploy.sh expects:
#
#     Client :80/:443
#         -> Caddy            TLS edge, on-demand certs for customer domains
#         -> Nginx            127.0.0.1:8080, loopback only
#         -> PHP-FPM          /run/php/siteforge.sock  (pool runs as DEPLOY_USER)
#         -> /var/www/builder/current/public
#
# Safe to re-run: every step checks before it changes anything.
#
# Usage (as root):
#     cp deploy/deploy.env.example deploy/deploy.env   # then edit
#     bash deploy/provision.sh
#
# Config is read from, in order of preference:
#     $DEPLOY_ENV_FILE
#     /var/www/builder/shared/deploy.env
#     <repo>/deploy/deploy.env
# Any value may be overridden by an exported environment variable.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ---------------------------------------------------------------------------
# Output helpers
# ---------------------------------------------------------------------------
if [[ -t 1 ]]; then
    C_RESET=$'\e[0m'; C_BLUE=$'\e[34m'; C_GREEN=$'\e[32m'
    C_YELLOW=$'\e[33m'; C_RED=$'\e[31m'; C_DIM=$'\e[2m'
else
    C_RESET=''; C_BLUE=''; C_GREEN=''; C_YELLOW=''; C_RED=''; C_DIM=''
fi

step()  { printf '\n%s==>%s %s\n' "$C_BLUE" "$C_RESET" "$*"; }
ok()    { printf '  %s+%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
skip()  { printf '  %s.%s %s\n' "$C_DIM" "$C_RESET" "$*"; }
warn()  { printf '  %s!%s %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
die()   { printf '\n%sERROR:%s %s\n' "$C_RED" "$C_RESET" "$*" >&2; exit 1; }

CHANGED=()
record() { CHANGED+=("$1"); }

# ---------------------------------------------------------------------------
# Preconditions
# ---------------------------------------------------------------------------
[[ "$(id -u)" -eq 0 ]] || die "Run as root:  su - root -c 'bash $0'"

if ! grep -q 'Ubuntu' /etc/os-release 2>/dev/null; then
    die "This script targets Ubuntu. Refusing to run on an unknown distribution."
fi

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
load_config() {
    local candidate
    for candidate in \
        "${DEPLOY_ENV_FILE:-}" \
        "/var/www/builder/shared/deploy.env" \
        "$SCRIPT_DIR/deploy.env"
    do
        [[ -n "$candidate" && -f "$candidate" ]] || continue
        step "Loading config from $candidate"
        set -a
        # shellcheck disable=SC1090
        source "$candidate"
        set +a
        CONFIG_FILE="$candidate"
        return 0
    done
    return 1
}

CONFIG_FILE=""
load_config || true

APP_HOST="${APP_HOST:-}"
SITES_DOMAIN="${SITES_DOMAIN:-}"
ACME_EMAIL="${ACME_EMAIL:-}"
DEPLOY_USER="${DEPLOY_USER:-forge}"
APP_BASE="${APP_BASE:-/var/www/builder}"
SITES_ROOT="${SITES_ROOT:-/srv/websites}"
PHP_VERSION="${PHP_VERSION:-8.4}"
DB_DATABASE="${DB_DATABASE:-siteforge}"
DB_USERNAME="${DB_USERNAME:-siteforge}"
DB_PASSWORD="${DB_PASSWORD:-}"

[[ -n "$APP_HOST" ]]     || die "APP_HOST is not set (deploy/deploy.env)"
[[ -n "$SITES_DOMAIN" ]] || die "SITES_DOMAIN is not set (deploy/deploy.env)"
[[ -n "$ACME_EMAIL" ]]   || die "ACME_EMAIL is not set (deploy/deploy.env)"

if [[ "$APP_HOST" == *"$SITES_DOMAIN" ]]; then
    die "APP_HOST ($APP_HOST) must not sit inside SITES_DOMAIN ($SITES_DOMAIN) — \
the on-demand TLS block would fight the app's managed certificate."
fi

SHARED_DIR="$APP_BASE/shared"
PHP_FPM_SOCK="/run/php/siteforge.sock"

cat <<SUMMARY

  SiteForge provisioning
  ----------------------
  App host      : $APP_HOST
  Sites domain  : $SITES_DOMAIN  (wildcard: *.$SITES_DOMAIN)
  ACME email    : $ACME_EMAIL
  Deploy user   : $DEPLOY_USER
  App base      : $APP_BASE
  Sites root    : $SITES_ROOT
  PHP           : $PHP_VERSION
  Database      : $DB_DATABASE (user $DB_USERNAME)

SUMMARY

export DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------------------
# 1. Repository signing keys
#
# Laravel Forge's provisioner writes the sources.list.d entries before the
# keyrings they reference, and if it dies partway the keyring files are left
# zero-length. Every third-party repo then fails signature verification, so
# `apt-get update` returns non-zero and PHP can never install — which is
# exactly how this box arrived. Repair any empty keyring before touching apt.
# ---------------------------------------------------------------------------
step "Repository signing keys"

fetch_key() { wget -q --timeout=20 --tries=3 -O "$2" "$1"; }

install_keyring() {
    local name="$1" dest="$2"; shift 2
    if [[ -s "$dest" ]]; then
        skip "$name keyring present"
        return 0
    fi
    local tmp; tmp="$(mktemp -d)"
    : > "$tmp/combined.asc"
    local url
    for url in "$@"; do
        if fetch_key "$url" "$tmp/one.asc"; then
            cat "$tmp/one.asc" >> "$tmp/combined.asc"
        else
            warn "could not fetch key for $name from $url"
        fi
    done
    if [[ -s "$tmp/combined.asc" ]]; then
        gpg --dearmor < "$tmp/combined.asc" > "$dest"
        chmod 644 "$dest"
        ok "$name keyring installed ($(stat -c%s "$dest") bytes)"
        record "repaired $name apt keyring"
    else
        warn "$name keyring could not be repaired — its repo will stay unusable"
    fi
    rm -rf "$tmp"
}

[[ -d /etc/apt/keyrings ]] || install -d -m 755 /etc/apt/keyrings

if [[ -f /etc/apt/sources.list.d/nginx.list ]]; then
    install_keyring "nginx.org" /usr/share/keyrings/nginx-archive-keyring.gpg \
        "https://nginx.org/keys/nginx_signing.key"
fi
if [[ -f /etc/apt/sources.list.d/redis.list ]]; then
    install_keyring "redis.io" /usr/share/keyrings/redis-archive-keyring.gpg \
        "https://packages.redis.io/gpg"
fi
if [[ -f /etc/apt/sources.list.d/nodesource.list ]]; then
    install_keyring "nodesource" /etc/apt/keyrings/nodesource.gpg \
        "https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key"
fi
if [[ -f /etc/apt/sources.list.d/ondrej-php.list ]]; then
    # Two keys: the Launchpad PPA key and the ppa.setup-php.com mirror key.
    install_keyring "ondrej-php" /usr/share/keyrings/ondrej-php.gpg \
        "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x4F4EA0AAE5267A6C" \
        "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x71DAEAAB4AD4CAB6"
fi

# ---------------------------------------------------------------------------
# 2. Packages
# ---------------------------------------------------------------------------
step "Refreshing apt indexes"
if apt-get update -y -qq; then
    ok "apt index up to date"
else
    die "apt-get update failed — a repository is still misconfigured (see above)"
fi

PHP_PKGS=(
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql"
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl"
    "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath"
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-redis" "php${PHP_VERSION}-sqlite3"
)
BASE_PKGS=(
    # nginx and mysql-server are listed explicitly because a genuinely bare
    # Ubuntu image has neither. They only appeared to be "already present" on
    # the first box because a partial Forge run had installed them.
    nginx mysql-server
    git curl unzip ca-certificates gnupg
    redis-server supervisor ufw
    debian-keyring debian-archive-keyring apt-transport-https
)

step "Installing packages"
MISSING=()
for pkg in "${PHP_PKGS[@]}" "${BASE_PKGS[@]}"; do
    dpkg -s "$pkg" >/dev/null 2>&1 || MISSING+=("$pkg")
done

if ((${#MISSING[@]})); then
    printf '  installing: %s\n' "${MISSING[*]}"
    apt-get install -y -qq "${MISSING[@]}"
    ok "installed ${#MISSING[@]} package(s)"
    record "installed packages: ${MISSING[*]}"
else
    skip "all packages already present"
fi

# ---------------------------------------------------------------------------
# 3. Composer
# ---------------------------------------------------------------------------
step "Composer"
if command -v composer >/dev/null 2>&1; then
    skip "composer already installed ($(composer --version 2>/dev/null | head -1))"
else
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [[ "$EXPECTED" == "$ACTUAL" ]] || die "Composer installer checksum mismatch — aborting"
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
    ok "composer installed"
    record "installed composer"
fi

# ---------------------------------------------------------------------------
# 4. Caddy
# ---------------------------------------------------------------------------
step "Caddy"
if command -v caddy >/dev/null 2>&1; then
    skip "caddy already installed ($(caddy version | head -1))"
else
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
        | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
        > /etc/apt/sources.list.d/caddy-stable.list
    apt-get update -y -qq
    apt-get install -y -qq caddy
    ok "caddy installed"
    record "installed caddy"
fi

# ---------------------------------------------------------------------------
# Node.js and npm
#
# Ubuntu's nodejs package does not pull in npm, and browsershot needs it.
# Prefer the nodesource build, which bundles a matching npm.
# ---------------------------------------------------------------------------
step "Node.js and npm"
if command -v npm >/dev/null 2>&1; then
    skip "npm present (node $(node -v 2>/dev/null), npm $(npm -v 2>/dev/null))"
else
    apt-get install -y -qq nodejs || apt-get install -y -qq npm
    if command -v npm >/dev/null 2>&1; then
        ok "node $(node -v), npm $(npm -v)"
        record "installed node/npm"
    else
        warn "npm still unavailable — poster export will not work"
    fi
fi

# ---------------------------------------------------------------------------
# Headless Chromium for spatie/browsershot (poster export)
# ---------------------------------------------------------------------------
step "Headless Chromium (browsershot)"
if [[ -d /usr/lib/node_modules/puppeteer ]]; then
    skip "puppeteer already installed"
else
    # Puppeteer ships its own pinned Chrome build; far more predictable than
    # Ubuntu's chromium snap, which cannot run from a service context.
    if PUPPETEER_CACHE_DIR=/usr/lib/node_modules/.puppeteer-cache \
        npm install -g --silent puppeteer >/tmp/puppeteer-install.log 2>&1
    then
        ok "puppeteer + bundled Chrome installed"
        record "installed puppeteer"
    else
        warn "puppeteer install failed — poster export (PosterExporter) will not work."
        warn "See /tmp/puppeteer-install.log. Everything else is unaffected."
    fi
fi

# Chrome needs these shared libraries; missing ones fail at render time only.
CHROME_LIBS=(libnss3 libatk1.0-0t64 libatk-bridge2.0-0t64 libcups2t64 libdrm2
             libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2
             libgbm1 libpango-1.0-0 libcairo2 libasound2t64)
MISSING_LIBS=()
for lib in "${CHROME_LIBS[@]}"; do
    dpkg -s "$lib" >/dev/null 2>&1 || MISSING_LIBS+=("$lib")
done
if ((${#MISSING_LIBS[@]})); then
    apt-get install -y -qq "${MISSING_LIBS[@]}" \
        && ok "installed Chrome shared libraries" \
        || warn "some Chrome libraries failed to install: ${MISSING_LIBS[*]}"
else
    skip "Chrome shared libraries present"
fi

# ---------------------------------------------------------------------------
# 5. Users and directory layout
# ---------------------------------------------------------------------------
step "Deploy user and directories"
if id -u "$DEPLOY_USER" >/dev/null 2>&1; then
    skip "user $DEPLOY_USER exists"
else
    adduser --disabled-password --gecos '' "$DEPLOY_USER"
    ok "created user $DEPLOY_USER"
    record "created user $DEPLOY_USER"
fi

install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" -m 755 \
    "$APP_BASE" "$APP_BASE/releases" "$SHARED_DIR"

# Laravel writes into these; they survive releases via symlink.
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" -m 775 \
    "$SHARED_DIR/storage" \
    "$SHARED_DIR/storage/app" \
    "$SHARED_DIR/storage/app/public" \
    "$SHARED_DIR/storage/app/website-data" \
    "$SHARED_DIR/storage/framework" \
    "$SHARED_DIR/storage/framework/cache" \
    "$SHARED_DIR/storage/framework/cache/data" \
    "$SHARED_DIR/storage/framework/sessions" \
    "$SHARED_DIR/storage/framework/testing" \
    "$SHARED_DIR/storage/framework/views" \
    "$SHARED_DIR/storage/logs"

# Published customer sites. PHP-FPM runs as DEPLOY_USER, so it owns these.
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" -m 755 \
    "$SITES_ROOT" "$SITES_ROOT/domains"

install -d -m 755 /var/log/php
ok "layout ready under $APP_BASE and $SITES_ROOT"

# ---------------------------------------------------------------------------
# 6. Strip Laravel Forge remnants
# ---------------------------------------------------------------------------
step "Removing Laravel Forge remnants"
FORGE_SUDOERS=(/etc/sudoers.d/composer /etc/sudoers.d/nginx
               /etc/sudoers.d/php-fpm /etc/sudoers.d/supervisor)
for f in "${FORGE_SUDOERS[@]}"; do
    if [[ -f "$f" ]]; then
        rm -f "$f"
        ok "removed $f"
        record "removed $f"
    fi
done

for f in /etc/nginx/sites-enabled/000-catch-all /etc/nginx/sites-available/000-catch-all \
         /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default; do
    if [[ -e "$f" ]]; then
        rm -f "$f"
        ok "removed $f"
        record "removed $f"
    fi
done

if [[ -d /etc/nginx/forge-conf ]]; then
    rm -rf /etc/nginx/forge-conf
    ok "removed /etc/nginx/forge-conf"
    record "removed /etc/nginx/forge-conf"
fi

if [[ -d "/home/$DEPLOY_USER/.forge" ]]; then
    rm -rf "/home/$DEPLOY_USER/.forge"
    ok "removed ~/.forge"
    record "removed ~/.forge"
fi

# Replace Forge's broad sudo grants with exactly what deploy.sh needs.
SUDOERS_FILE=/etc/sudoers.d/siteforge-deploy
cat > "$SUDOERS_FILE" <<SUDOERS
# SiteForge — the minimum privileges deploy/deploy.sh and rollback.sh need.
# Written by deploy/provision.sh. Do not edit by hand.
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php$PHP_VERSION-fpm
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload nginx
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload caddy
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart siteforge\:*
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart siteforge-*
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl start siteforge-*
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl stop siteforge-*
$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl status
SUDOERS
chmod 440 "$SUDOERS_FILE"
visudo -cf "$SUDOERS_FILE" >/dev/null || die "Generated sudoers file is invalid"
ok "wrote $SUDOERS_FILE (scoped to deploy actions)"

# ---------------------------------------------------------------------------
# 7. Conflicting web servers
#
# This provider's base image ships apache2 enabled and bound to :80, which
# both starves Caddy of the port and is why nginx has never started. Mask
# rather than purge: reversible, and it survives a dependency pulling apache
# back in.
# ---------------------------------------------------------------------------
step "Conflicting web servers"
if dpkg -s apache2 >/dev/null 2>&1; then
    if systemctl is-active --quiet apache2 || systemctl is-enabled --quiet apache2; then
        systemctl stop apache2 2>/dev/null || true
        systemctl disable apache2 2>/dev/null || true
        systemctl mask apache2 2>/dev/null || true
        ok "apache2 stopped, disabled and masked (it was holding :80)"
        record "masked apache2"
    else
        skip "apache2 installed but already inert"
    fi
else
    skip "apache2 not installed"
fi

# ---------------------------------------------------------------------------
# 8. PHP-FPM pool
# ---------------------------------------------------------------------------
step "PHP-FPM pool"
POOL_DIR="/etc/php/$PHP_VERSION/fpm/pool.d"
[[ -d "$POOL_DIR" ]] || die "$POOL_DIR missing — is php$PHP_VERSION-fpm installed?"

sed -e "s|PHP_VERSION|$PHP_VERSION|g" \
    -e "s|DEPLOY_USER|$DEPLOY_USER|g" \
    "$SCRIPT_DIR/php-fpm.pool.conf" > "$POOL_DIR/siteforge.conf"
ok "installed $POOL_DIR/siteforge.conf"

# The stock www pool would also bind a socket we never use.
if [[ -f "$POOL_DIR/www.conf" ]]; then
    mv "$POOL_DIR/www.conf" "$POOL_DIR/www.conf.disabled"
    ok "disabled stock www pool"
    record "disabled stock php-fpm www pool"
fi

# ---------------------------------------------------------------------------
# 9. Nginx origin (loopback only)
# ---------------------------------------------------------------------------
step "Nginx origin config"

# nginx worker user must match the PHP-FPM pool user so it can read the socket.
if grep -qE "^\s*user\s+$DEPLOY_USER;" /etc/nginx/nginx.conf; then
    skip "nginx already runs as $DEPLOY_USER"
else
    sed -i -E "s|^\s*user\s+.*;|user $DEPLOY_USER;|" /etc/nginx/nginx.conf
    ok "set nginx user to $DEPLOY_USER"
    record "nginx user -> $DEPLOY_USER"
fi

SITES_DOMAIN_REGEX="${SITES_DOMAIN//./\\.}"
sed -e "s|APP_HOST|$APP_HOST|g" \
    -e "s|SITES_DOMAIN|$SITES_DOMAIN_REGEX|g" \
    -e "s|PHP_FPM_SOCK|$PHP_FPM_SOCK|g" \
    -e "s|APP_ROOT|$APP_BASE/current/public|g" \
    -e "s|SITES_ROOT|$SITES_ROOT|g" \
    "$SCRIPT_DIR/nginx.origin.conf" > /etc/nginx/sites-available/siteforge-origin

ln -sfn /etc/nginx/sites-available/siteforge-origin \
        /etc/nginx/sites-enabled/siteforge-origin
ok "installed siteforge-origin (127.0.0.1:8080)"

grep -q 'listen 127.0.0.1:8080' /etc/nginx/sites-available/siteforge-origin \
    || die "Origin config is not loopback-only — refusing to continue"

# ---------------------------------------------------------------------------
# 10. Caddy edge
# ---------------------------------------------------------------------------
step "Caddy edge config"
sed -e "s|CADDY_EMAIL|$ACME_EMAIL|g" \
    -e "s|APP_HOST|$APP_HOST|g" \
    "$SCRIPT_DIR/caddy.edge.Caddyfile" > /etc/caddy/Caddyfile
ok "installed /etc/caddy/Caddyfile"

# ---------------------------------------------------------------------------
# 11. MySQL
# ---------------------------------------------------------------------------
step "MySQL"
MYSQL_CNF=/etc/mysql/mysql.conf.d/mysqld.cnf
if grep -qE '^\s*bind-address\s*=\s*127\.0\.0\.1' "$MYSQL_CNF"; then
    skip "mysql config already bound to 127.0.0.1"
else
    sed -i -E 's|^\s*bind-address\s*=.*|bind-address = 127.0.0.1|' "$MYSQL_CNF"
    ok "bound mysql to 127.0.0.1 (was listening on all interfaces)"
    record "mysql bind-address -> 127.0.0.1"
fi

# Gate the restart on what mysqld is *actually* listening on, not on whether
# this run edited the file. A previous run that wrote the config but died
# before restarting would otherwise leave the port publicly exposed forever.
if ss -tln 2>/dev/null | grep -qE '(\*|0\.0\.0\.0):3306\b'; then
    systemctl restart mysql
    ok "restarted mysql to apply the loopback bind"
    record "restarted mysql (was publicly listening)"
else
    skip "mysql not listening publicly"
fi

if [[ -z "$DB_PASSWORD" ]]; then
    # Reuse the password already in shared/.env if we are re-running.
    if [[ -f "$SHARED_DIR/.env" ]] && grep -q '^DB_PASSWORD=' "$SHARED_DIR/.env"; then
        DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' "$SHARED_DIR/.env" | cut -d= -f2-)"
        skip "reusing existing DB password from shared/.env"
    fi
fi
if [[ -z "$DB_PASSWORD" ]]; then
    # head -c on /dev/urandom directly: piping `tr </dev/urandom | head` kills
    # tr with SIGPIPE, which `set -o pipefail` turns into a fatal error.
    DB_PASSWORD="$(head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | cut -c1-32)"
    [[ ${#DB_PASSWORD} -ge 24 ]] || die "Failed to generate a database password"
    ok "generated a new database password"
    record "generated database password"
fi

mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
ok "database $DB_DATABASE and user $DB_USERNAME ready"

# Persist the password back into the config file so re-runs are stable.
if [[ -n "$CONFIG_FILE" ]] && grep -q '^DB_PASSWORD=$' "$CONFIG_FILE" 2>/dev/null; then
    sed -i "s|^DB_PASSWORD=$|DB_PASSWORD=$DB_PASSWORD|" "$CONFIG_FILE"
    ok "wrote password back to $CONFIG_FILE"
fi

# ---------------------------------------------------------------------------
# 12. Memcached — bind to loopback
# ---------------------------------------------------------------------------
step "Memcached"
if [[ -f /etc/memcached.conf ]]; then
    if grep -qE '^-l 127\.0\.0\.1$' /etc/memcached.conf; then
        skip "memcached config already bound to 127.0.0.1"
    else
        # Drop the IPv6 any-address line too; -l 0.0.0.0 and -l ::1 are both
        # listed by default and either alone re-exposes the port.
        sed -i -E 's|^-l 0\.0\.0\.0$|-l 127.0.0.1|; s|^-l ::1$|-l ::1|' /etc/memcached.conf
        ok "bound memcached to 127.0.0.1 (was exposed on all interfaces)"
        record "memcached -l -> 127.0.0.1"
    fi
    # Same reasoning as MySQL: trust the listener, not the edit.
    if ss -tln 2>/dev/null | grep -qE '(\*|0\.0\.0\.0):11211\b'; then
        systemctl restart memcached && ok "restarted memcached to apply the loopback bind" \
            || warn "memcached restart failed"
        record "restarted memcached (was publicly listening)"
    else
        skip "memcached not listening publicly"
    fi
else
    skip "memcached not installed"
fi

# ---------------------------------------------------------------------------
# 13. Redis
# ---------------------------------------------------------------------------
step "Redis"
if redis-cli ping >/dev/null 2>&1; then
    skip "redis responding"
else
    systemctl enable --now redis-server
    ok "redis started"
fi

# ---------------------------------------------------------------------------
# 14. Supervisor (Horizon + scheduler)
# ---------------------------------------------------------------------------
step "Supervisor programs"
sed -e "s|APP_BASE|$APP_BASE/current|g" \
    -e "s|DEPLOY_USER|$DEPLOY_USER|g" \
    -e "s|PHP_BIN|/usr/bin/php$PHP_VERSION|g" \
    "$SCRIPT_DIR/supervisor/siteforge.conf" > /etc/supervisor/conf.d/siteforge.conf
ok "installed /etc/supervisor/conf.d/siteforge.conf"

# ---------------------------------------------------------------------------
# 15. Shared .env
# ---------------------------------------------------------------------------
step "Shared environment file"

# deploy.sh runs from inside a release, where there is no deploy.env next to
# it. Park the config in shared/ so every future deploy resolves it.
if [[ -n "$CONFIG_FILE" && "$CONFIG_FILE" != "$SHARED_DIR/deploy.env" ]]; then
    cp "$CONFIG_FILE" "$SHARED_DIR/deploy.env"
    chown "$DEPLOY_USER:$DEPLOY_USER" "$SHARED_DIR/deploy.env"
    chmod 600 "$SHARED_DIR/deploy.env"
    ok "copied deployment config to $SHARED_DIR/deploy.env"
fi

if [[ -f "$SHARED_DIR/.env" ]]; then
    skip "$SHARED_DIR/.env exists — leaving it alone"
else
    cat > "$SHARED_DIR/.env" <<ENVFILE
APP_NAME=SiteForge
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$APP_HOST

CDN_BASE_URL=https://$APP_HOST

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@$APP_HOST"
MAIL_FROM_NAME="\${APP_NAME}"

# --- AI website generation -------------------------------------------------
# REQUIRED: generation fails without this.
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-opus-4-8
ANTHROPIC_MAX_TOKENS=64000
ANTHROPIC_CACHE_TTL=1h

# --- Customer site hosting -------------------------------------------------
SITES_DOMAIN=$SITES_DOMAIN
SITES_PUBLISH_PATH=$SITES_ROOT
HOSTING_DRIVER=filesystem
CADDY_ASK_URL=http://127.0.0.1:8080/caddy/allowed
NGINX_CUSTOMERS_PATH=/etc/nginx/sites-available/customers
SITES_GENERATION_COST=1
CREDIT_UNIT_CENTS=2000
DOMAIN_DEFAULT_CREDITS=5
DOMAIN_ADDON_DNS_CREDITS=1
DOMAIN_ADDON_EMAIL_CREDITS=1
DOMAIN_ADDON_ID_PROTECT_CREDITS=2
SITES_MAX_IMAGES=10
WEBSITE_DATA_PATH=$SHARED_DIR/storage/app/website-data

# --- Marketing services ----------------------------------------------------
EDITING_SUBSCRIPTION_PRICE=R299/year
EDITING_SUBSCRIPTION_YEARS=1
NEWSLETTER_GENERATION_COST=2
POSTER_GENERATION_COST=3

# --- Domain reseller (HostAfrica) ------------------------------------------
HOSTAFRICA_ENDPOINT=https://my.hostafrica.com/modules/addons/DomainsReseller/api/index.php
HOSTAFRICA_USERNAME=
HOSTAFRICA_API_KEY=
HOSTAFRICA_NS1=ns1.hostafrica.com
HOSTAFRICA_NS2=ns2.hostafrica.com
HOSTAFRICA_DEFAULT_TLDS=co.za,com,net,org,africa
ENVFILE
    chown "$DEPLOY_USER:$DEPLOY_USER" "$SHARED_DIR/.env"
    chmod 600 "$SHARED_DIR/.env"
    ok "created $SHARED_DIR/.env"
    record "created shared/.env (ANTHROPIC_API_KEY still blank)"
fi

# ---------------------------------------------------------------------------
# 16. Database backups
#
# rollback.sh flips code, never schema. A destructive migration can only be
# undone from a dump, and this box has no binlog and no point-in-time
# recovery — so a nightly dump is the entire safety net.
# ---------------------------------------------------------------------------
step "Database backups"
install -m 750 "$SCRIPT_DIR/backup.sh" /usr/local/bin/siteforge-backup
cat > /etc/cron.d/siteforge-backup <<CRON
# SiteForge — nightly MySQL backup. Written by deploy/provision.sh.
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
30 2 * * * root /usr/local/bin/siteforge-backup >> /var/log/siteforge-backup.log 2>&1
CRON
chmod 644 /etc/cron.d/siteforge-backup
ok "nightly backup installed (02:30 UTC -> /var/backups/siteforge)"

# ---------------------------------------------------------------------------
# 17. Firewall
# ---------------------------------------------------------------------------
step "Firewall"
ufw allow OpenSSH >/dev/null 2>&1 || true
ufw allow 80/tcp  >/dev/null 2>&1 || true
ufw allow 443/tcp >/dev/null 2>&1 || true
if ufw status | grep -q '^Status: active'; then
    skip "ufw already active"
else
    ufw --force enable >/dev/null
    ok "ufw enabled (22, 80, 443)"
    record "enabled ufw"
fi

# ---------------------------------------------------------------------------
# 18. Validate and start services
# ---------------------------------------------------------------------------
step "Validating configuration"
nginx -t 2>&1 | sed 's/^/  /'
caddy validate --config /etc/caddy/Caddyfile 2>&1 | tail -3 | sed 's/^/  /' || \
    warn "caddy validate reported issues (see above)"

step "Starting services"
systemctl enable "php$PHP_VERSION-fpm" nginx caddy redis-server supervisor >/dev/null 2>&1 || true
systemctl restart "php$PHP_VERSION-fpm"
# nginx and caddy may be in a failed state from a previous partial run, and
# systemd will not reload a unit that is not running — restart if reload fails.
systemctl reload nginx  || systemctl restart nginx
systemctl reload caddy  || systemctl restart caddy
supervisorctl reread >/dev/null 2>&1 || true
supervisorctl update >/dev/null 2>&1 || true

for svc in "php$PHP_VERSION-fpm" nginx caddy; do
    if systemctl is-active --quiet "$svc"; then
        ok "$svc active"
    else
        warn "$svc is NOT active — check: systemctl status $svc"
    fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
printf '\n%s============================================================%s\n' "$C_GREEN" "$C_RESET"
printf '%s Provisioning complete%s\n' "$C_GREEN" "$C_RESET"
printf '%s============================================================%s\n' "$C_GREEN" "$C_RESET"

if ((${#CHANGED[@]})); then
    printf '\nChanges made this run:\n'
    printf '  - %s\n' "${CHANGED[@]}"
else
    printf '\nNo changes — server already in the desired state.\n'
fi

cat <<NEXT

Listening now:
  :80/:443        caddy   (TLS edge, on-demand certs)
  127.0.0.1:8080  nginx   (origin, loopback only)
  $PHP_FPM_SOCK   php-fpm (pool user: $DEPLOY_USER)

Next steps:
  1. DNS -> this server:
       A   $APP_HOST
       A   $SITES_DOMAIN
       A   *.$SITES_DOMAIN
  2. Add the deploy key to GitHub (read-only):
       sudo -u $DEPLOY_USER ssh-keygen -t ed25519 -N '' -f /home/$DEPLOY_USER/.ssh/id_ed25519
       cat /home/$DEPLOY_USER/.ssh/id_ed25519.pub
  3. Fill in ANTHROPIC_API_KEY (and HostAfrica creds) in:
       $SHARED_DIR/.env
  4. Ship the first release:
       sudo -u $DEPLOY_USER bash $SCRIPT_DIR/deploy.sh

NEXT
