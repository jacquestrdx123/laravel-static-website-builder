#!/usr/bin/env bash
#
# SiteForge — atomic release deploy (no Laravel Forge).
#
#   /var/www/builder/
#       releases/20260803-104200-a1b2c3d/   <- built in full, then activated
#       shared/.env                          <- symlinked into every release
#       shared/storage/                      <- symlinked into every release
#       shared/repo.git/                     <- bare mirror, cheap fetches
#       current -> releases/20260803-104200-a1b2c3d
#
# The `current` symlink only moves once the new release is fully built and has
# passed a boot check, so a failed deploy leaves the running site untouched.
#
# Usage (as the deploy user):
#     bash deploy/deploy.sh                # deploy REPO_BRANCH
#     REF=v1.2.0 bash deploy/deploy.sh     # deploy a tag/branch/sha
#     SKIP_MIGRATE=1 bash deploy/deploy.sh # skip migrations
#     DRY_RUN=1 bash deploy/deploy.sh      # build but do not activate

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ -t 1 ]]; then
    C_RESET=$'\e[0m'; C_BLUE=$'\e[34m'; C_GREEN=$'\e[32m'
    C_YELLOW=$'\e[33m'; C_RED=$'\e[31m'; C_DIM=$'\e[2m'
else
    C_RESET=''; C_BLUE=''; C_GREEN=''; C_YELLOW=''; C_RED=''; C_DIM=''
fi

step() { printf '\n%s==>%s %s\n' "$C_BLUE" "$C_RESET" "$*"; }
ok()   { printf '  %s+%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn() { printf '  %s!%s %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
die()  { printf '\n%sDEPLOY FAILED:%s %s\n' "$C_RED" "$C_RESET" "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
for candidate in \
    "${DEPLOY_ENV_FILE:-}" \
    "/var/www/builder/shared/deploy.env" \
    "$SCRIPT_DIR/deploy.env"
do
    if [[ -n "$candidate" && -f "$candidate" ]]; then
        set -a
        # shellcheck disable=SC1090
        source "$candidate"
        set +a
        break
    fi
done

APP_BASE="${APP_BASE:-/var/www/builder}"
PHP_VERSION="${PHP_VERSION:-8.4}"
REPO_URL="${REPO_URL:-}"
REPO_BRANCH="${REPO_BRANCH:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
REF="${REF:-$REPO_BRANCH}"

PHP_BIN="${PHP_BIN:-/usr/bin/php$PHP_VERSION}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"

SHARED_DIR="$APP_BASE/shared"
RELEASES_DIR="$APP_BASE/releases"
CURRENT_LINK="$APP_BASE/current"
MIRROR="$SHARED_DIR/repo.git"

[[ -n "$REPO_URL" ]] || die "REPO_URL is not set (shared/deploy.env)"
[[ -f "$SHARED_DIR/.env" ]] || die "$SHARED_DIR/.env is missing — run provision.sh first"
[[ -x "$PHP_BIN" ]] || die "$PHP_BIN not found"
[[ -x "$COMPOSER_BIN" ]] || die "$COMPOSER_BIN not found"

mkdir -p "$RELEASES_DIR"

# ---------------------------------------------------------------------------
# Concurrency guard — two deploys at once would corrupt the release set
# ---------------------------------------------------------------------------
LOCK_FILE="$SHARED_DIR/deploy.lock"
exec 9>"$LOCK_FILE"
flock -n 9 || die "Another deploy is already running (lock: $LOCK_FILE)"

# ---------------------------------------------------------------------------
# Fetch source
# ---------------------------------------------------------------------------
step "Fetching source"
if [[ -d "$MIRROR" ]]; then
    git --git-dir="$MIRROR" remote set-url origin "$REPO_URL"
    git --git-dir="$MIRROR" fetch --prune --tags origin '+refs/heads/*:refs/heads/*'
    ok "updated mirror"
else
    git clone --mirror "$REPO_URL" "$MIRROR"
    ok "created mirror at $MIRROR"
fi

SHA="$(git --git-dir="$MIRROR" rev-parse --verify "$REF^{commit}" 2>/dev/null)" \
    || die "Ref '$REF' not found in $REPO_URL"
SHORT_SHA="${SHA:0:7}"
SUBJECT="$(git --git-dir="$MIRROR" log -1 --pretty=%s "$SHA")"
ok "$REF -> $SHORT_SHA  ${C_DIM}${SUBJECT}${C_RESET}"

PREVIOUS=""
if [[ -L "$CURRENT_LINK" ]]; then
    PREVIOUS="$(readlink -f "$CURRENT_LINK")"
    if [[ "$(cat "$PREVIOUS/REVISION" 2>/dev/null)" == "$SHA" ]]; then
        warn "$SHORT_SHA is already the live release"
    fi
fi

# ---------------------------------------------------------------------------
# Build the new release
# ---------------------------------------------------------------------------
RELEASE_NAME="$(date -u +%Y%m%d-%H%M%S)-$SHORT_SHA"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_NAME"

cleanup_failed() {
    local code=$?
    if [[ $code -ne 0 && -d "$RELEASE_DIR" ]]; then
        printf '\n%s!%s Removing failed release %s\n' "$C_YELLOW" "$C_RESET" "$RELEASE_NAME"
        rm -rf "$RELEASE_DIR"
        if [[ -n "$PREVIOUS" ]]; then
            printf '%s!%s Live release is unchanged: %s\n' \
                "$C_YELLOW" "$C_RESET" "$(basename "$PREVIOUS")"
        fi
    fi
    exit $code
}
trap cleanup_failed EXIT

step "Building release $RELEASE_NAME"
git clone --quiet --no-checkout "$MIRROR" "$RELEASE_DIR"
git -C "$RELEASE_DIR" checkout --quiet --detach "$SHA"
rm -rf "$RELEASE_DIR/.git"
printf '%s\n' "$SHA" > "$RELEASE_DIR/REVISION"
ok "checked out $SHORT_SHA"

# Shared state: a release must never own .env or storage.
ln -sfn "$SHARED_DIR/.env" "$RELEASE_DIR/.env"
rm -rf "$RELEASE_DIR/storage"
ln -sfn "$SHARED_DIR/storage" "$RELEASE_DIR/storage"
ok "linked shared .env and storage"

step "Installing dependencies"
(
    cd "$RELEASE_DIR"
    "$COMPOSER_BIN" install \
        --no-dev --no-interaction --prefer-dist \
        --optimize-autoloader --no-progress
)
ok "composer install complete"

step "Optimising"
(
    cd "$RELEASE_DIR"
    "$PHP_BIN" artisan storage:link --quiet || true
    "$PHP_BIN" artisan optimize
)
ok "config, routes, views and events cached"

# ---------------------------------------------------------------------------
# Boot check — catch a broken build before it can serve traffic
# ---------------------------------------------------------------------------
step "Boot check"
(
    cd "$RELEASE_DIR"
    "$PHP_BIN" artisan about --only=environment >/dev/null
) || die "New release fails to boot — not activating. Live site untouched."
ok "application boots and reads its config"

# ---------------------------------------------------------------------------
# Migrations
# ---------------------------------------------------------------------------
if [[ "${SKIP_MIGRATE:-0}" == "1" ]]; then
    warn "skipping migrations (SKIP_MIGRATE=1)"
else
    step "Running migrations"
    (cd "$RELEASE_DIR" && "$PHP_BIN" artisan migrate --force --no-interaction)
    ok "migrations applied"
fi

# ---------------------------------------------------------------------------
# Activate
# ---------------------------------------------------------------------------
if [[ "${DRY_RUN:-0}" == "1" ]]; then
    trap - EXIT
    warn "DRY_RUN=1 — built $RELEASE_NAME but did not activate it"
    printf '\nActivate manually with:\n  ln -sfn %s %s.tmp && mv -Tf %s.tmp %s\n\n' \
        "$RELEASE_DIR" "$CURRENT_LINK" "$CURRENT_LINK" "$CURRENT_LINK"
    exit 0
fi

step "Activating"
# ln + mv -T is atomic: a request sees either the old or the new target,
# never a missing symlink.
ln -sfn "$RELEASE_DIR" "$CURRENT_LINK.tmp"
mv -Tf "$CURRENT_LINK.tmp" "$CURRENT_LINK"
ok "current -> $RELEASE_NAME"

trap - EXIT

# ---------------------------------------------------------------------------
# Reload services
# ---------------------------------------------------------------------------
step "Reloading services"
# PHP-FPM caches resolved realpaths; without this it keeps serving the old release.
sudo /usr/bin/systemctl reload "php$PHP_VERSION-fpm" && ok "php$PHP_VERSION-fpm reloaded"

if command -v supervisorctl >/dev/null 2>&1; then
    sudo /usr/bin/supervisorctl restart siteforge-horizon siteforge-scheduler \
        >/dev/null 2>&1 && ok "horizon and scheduler restarted" \
        || warn "supervisor restart failed — check: sudo supervisorctl status"
fi

# ---------------------------------------------------------------------------
# Verify the live site
# ---------------------------------------------------------------------------
step "Verifying"
HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 \
    -H "Host: ${APP_HOST:-localhost}" http://127.0.0.1:8080/ || echo 000)"
case "$HTTP_CODE" in
    2*|3*) ok "origin responded HTTP $HTTP_CODE" ;;
    000)   warn "origin did not respond — check: sudo journalctl -u nginx -n 50" ;;
    *)     warn "origin responded HTTP $HTTP_CODE — investigate before trusting this deploy"
           warn "rollback: bash $SCRIPT_DIR/rollback.sh" ;;
esac

# ---------------------------------------------------------------------------
# Prune old releases
# ---------------------------------------------------------------------------
step "Pruning old releases"
LIVE="$(basename "$(readlink -f "$CURRENT_LINK")")"
mapfile -t ALL < <(ls -1 "$RELEASES_DIR" | sort -r)
KEPT=0
for rel in "${ALL[@]}"; do
    if [[ "$rel" == "$LIVE" ]] || (( KEPT < KEEP_RELEASES )); then
        KEPT=$((KEPT + 1))
        continue
    fi
    rm -rf "${RELEASES_DIR:?}/$rel"
    printf '  %s-%s removed %s\n' "$C_DIM" "$C_RESET" "$rel"
done
ok "keeping $KEEP_RELEASES most recent release(s)"

printf '\n%s============================================================%s\n' "$C_GREEN" "$C_RESET"
printf '%s Deployed %s (%s)%s\n' "$C_GREEN" "$SHORT_SHA" "$SUBJECT" "$C_RESET"
printf '%s============================================================%s\n' "$C_GREEN" "$C_RESET"
if [[ -n "$PREVIOUS" ]]; then
    printf '\nPrevious release: %s\n' "$(basename "$PREVIOUS")"
    printf 'Roll back with:   bash %s/rollback.sh\n\n' "$SCRIPT_DIR"
fi
