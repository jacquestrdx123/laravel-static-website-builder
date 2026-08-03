#!/usr/bin/env bash
#
# SiteForge — nightly MySQL backup with rotation.
#
# Installed at /usr/local/bin/siteforge-backup by deploy/provision.sh and run
# from /etc/cron.d/siteforge-backup.
#
# Rollback flips code, never schema — a destructive migration needs one of
# these dumps. Keeps KEEP_DAYS of history and verifies each dump is complete
# before rotating anything out.

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/siteforge}"
KEEP_DAYS="${KEEP_DAYS:-14}"
ENV_FILE="${ENV_FILE:-/var/www/builder/shared/.env}"

log() { printf '%s  %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }
die() { printf '%s  ERROR: %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*" >&2; exit 1; }

[[ -f "$ENV_FILE" ]] || die "$ENV_FILE not found"

# Read credentials from the app's own .env so they can never drift apart.
envval() { sed -nE "s/^$1=(.*)\$/\1/p" "$ENV_FILE" | head -1; }
DB_DATABASE="$(envval DB_DATABASE)"
DB_USERNAME="$(envval DB_USERNAME)"
DB_PASSWORD="$(envval DB_PASSWORD)"
DB_HOST="$(envval DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"

[[ -n "$DB_DATABASE" && -n "$DB_USERNAME" ]] || die "DB credentials missing from $ENV_FILE"

install -d -m 700 "$BACKUP_DIR"

STAMP="$(date -u +%Y%m%d-%H%M%S)"
TARGET="$BACKUP_DIR/$DB_DATABASE-$STAMP.sql.gz"

# Credentials via a mode-600 defaults file — never on the command line, where
# any user on the box could read them out of `ps`.
CNF="$(mktemp)"
chmod 600 "$CNF"
trap 'rm -f "$CNF"' EXIT
cat > "$CNF" <<CNFEOF
[client]
host=$DB_HOST
user=$DB_USERNAME
password=$DB_PASSWORD
CNFEOF

log "dumping $DB_DATABASE -> $(basename "$TARGET")"

# --single-transaction keeps InnoDB consistent without locking out the app.
# --no-tablespaces: dumping tablespaces needs the global PROCESS privilege,
# which the app user deliberately does not have; without this mysqldump warns
# on every run and the noise trains you to ignore the log.
# pipefail is on, so a mysqldump failure aborts before the file is trusted.
mysqldump --defaults-extra-file="$CNF" \
    --single-transaction --quick --routines --triggers --events \
    --no-tablespaces --default-character-set=utf8mb4 \
    "$DB_DATABASE" | gzip -c > "$TARGET"

# A truncated dump is worse than no dump — it looks like a backup. Verify the
# gzip stream is intact and the dump reached its own end marker.
gzip -t "$TARGET" || die "dump is not a valid gzip stream — keeping it for inspection"
zcat "$TARGET" | tail -5 | grep -q 'Dump completed' \
    || die "dump has no completion marker — treating as truncated, not rotating"

chmod 600 "$TARGET"
log "ok: $(du -h "$TARGET" | cut -f1)"

# Only rotate once today's dump has been verified.
DELETED="$(find "$BACKUP_DIR" -name "$DB_DATABASE-*.sql.gz" -type f -mtime "+$KEEP_DAYS" -print -delete | wc -l)"
(( DELETED > 0 )) && log "rotated out $DELETED backup(s) older than $KEEP_DAYS days"

log "$(find "$BACKUP_DIR" -name "$DB_DATABASE-*.sql.gz" -type f | wc -l) backup(s) on disk"
