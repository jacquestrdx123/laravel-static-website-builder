#!/usr/bin/env bash
#
# SiteForge — roll back to a previous release.
#
# Flips the `current` symlink to an already-built release and reloads services.
# Because the target release is still on disk with its vendor/ intact, this
# takes about a second.
#
# Usage (as the deploy user):
#     bash deploy/rollback.sh                          # previous release
#     bash deploy/rollback.sh 20260803-104200-a1b2c3d  # a specific release
#     bash deploy/rollback.sh --list                   # show what is available
#
# DATABASE MIGRATIONS ARE NOT REVERSED. If the bad deploy ran a destructive
# migration, rolling back the code is not enough — restore from a backup.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ -t 1 ]]; then
    C_RESET=$'\e[0m'; C_BLUE=$'\e[34m'; C_GREEN=$'\e[32m'
    C_YELLOW=$'\e[33m'; C_RED=$'\e[31m'
else
    C_RESET=''; C_BLUE=''; C_GREEN=''; C_YELLOW=''; C_RED=''
fi

step() { printf '\n%s==>%s %s\n' "$C_BLUE" "$C_RESET" "$*"; }
ok()   { printf '  %s+%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn() { printf '  %s!%s %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
die()  { printf '\n%sROLLBACK FAILED:%s %s\n' "$C_RED" "$C_RESET" "$*" >&2; exit 1; }

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
RELEASES_DIR="$APP_BASE/releases"
CURRENT_LINK="$APP_BASE/current"

[[ -d "$RELEASES_DIR" ]] || die "$RELEASES_DIR does not exist"

mapfile -t RELEASES < <(ls -1 "$RELEASES_DIR" 2>/dev/null | sort -r)
((${#RELEASES[@]})) || die "No releases on disk"

LIVE=""
[[ -L "$CURRENT_LINK" ]] && LIVE="$(basename "$(readlink -f "$CURRENT_LINK")")"

# ---------------------------------------------------------------------------
# --list
# ---------------------------------------------------------------------------
if [[ "${1:-}" == "--list" || "${1:-}" == "-l" ]]; then
    printf '\nReleases in %s:\n\n' "$RELEASES_DIR"
    for rel in "${RELEASES[@]}"; do
        sha="$(cat "$RELEASES_DIR/$rel/REVISION" 2>/dev/null | cut -c1-7)"
        if [[ "$rel" == "$LIVE" ]]; then
            printf '  %s* %-32s %s  (live)%s\n' "$C_GREEN" "$rel" "$sha" "$C_RESET"
        else
            printf '    %-32s %s\n' "$rel" "$sha"
        fi
    done
    printf '\n'
    exit 0
fi

# ---------------------------------------------------------------------------
# Pick a target
# ---------------------------------------------------------------------------
TARGET="${1:-}"
if [[ -z "$TARGET" ]]; then
    for rel in "${RELEASES[@]}"; do
        if [[ "$rel" != "$LIVE" ]]; then
            TARGET="$rel"
            break
        fi
    done
    [[ -n "$TARGET" ]] || die "No previous release to roll back to (only $LIVE exists)"
fi

TARGET_DIR="$RELEASES_DIR/$TARGET"
[[ -d "$TARGET_DIR" ]] || die "Release '$TARGET' not found. Try: bash $0 --list"
[[ "$TARGET" != "$LIVE" ]] || die "'$TARGET' is already the live release"

# A half-deleted or half-built release must never be activated.
[[ -d "$TARGET_DIR/vendor" ]] || die "'$TARGET' has no vendor/ — it is not a complete release"
[[ -f "$TARGET_DIR/artisan" ]] || die "'$TARGET' has no artisan — it is not a complete release"

TARGET_SHA="$(cat "$TARGET_DIR/REVISION" 2>/dev/null | cut -c1-7)"

printf '\n  Rolling back\n'
printf '    from : %s\n' "${LIVE:-<none>}"
printf '    to   : %s (%s)\n' "$TARGET" "$TARGET_SHA"

# ---------------------------------------------------------------------------
# Flip
# ---------------------------------------------------------------------------
step "Activating $TARGET"
ln -sfn "$TARGET_DIR" "$CURRENT_LINK.tmp"
mv -Tf "$CURRENT_LINK.tmp" "$CURRENT_LINK"
ok "current -> $TARGET"

step "Reloading services"
sudo /usr/bin/systemctl reload "php$PHP_VERSION-fpm" && ok "php$PHP_VERSION-fpm reloaded"
if command -v supervisorctl >/dev/null 2>&1; then
    sudo /usr/bin/supervisorctl restart siteforge-horizon siteforge-scheduler \
        >/dev/null 2>&1 && ok "horizon and scheduler restarted" \
        || warn "supervisor restart failed — check: sudo supervisorctl status"
fi

step "Verifying"
HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 \
    -H "Host: ${APP_HOST:-localhost}" http://127.0.0.1:8080/ || echo 000)"
case "$HTTP_CODE" in
    2*|3*) ok "origin responded HTTP $HTTP_CODE" ;;
    *)     warn "origin responded HTTP $HTTP_CODE — the rollback target may also be broken" ;;
esac

printf '\n%sRolled back to %s (%s)%s\n' "$C_GREEN" "$TARGET" "$TARGET_SHA" "$C_RESET"
printf '%sNote:%s database migrations were NOT reversed.\n\n' "$C_YELLOW" "$C_RESET"
