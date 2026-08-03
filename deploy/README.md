# Deployment

Self-managed deployment for SiteForge. No Laravel Forge, no Envoyer, no agent
on the box — three shell scripts and a config file.

Full runbook (traffic flow, DNS, TLS, troubleshooting):
[`docs/hosting/caddy-nginx.md`](../docs/hosting/caddy-nginx.md)

---

## The three scripts

| Script | Runs as | What it does |
|--------|---------|--------------|
| `provision.sh` | root, once (re-runnable) | Turns a bare Ubuntu 24.04 box into a working host |
| `deploy.sh` | deploy user, every release | Builds a new release and activates it atomically |
| `rollback.sh` | deploy user, when needed | Flips `current` back to a previous release |

All three read the same config file, resolved in this order:

1. `$DEPLOY_ENV_FILE`
2. `/var/www/builder/shared/deploy.env`  ← where it lives in production
3. `deploy/deploy.env` (next to the scripts)

Start from [`deploy.env.example`](deploy.env.example). The filled-in copy holds
the database password and is gitignored.

---

## First bring-up

```bash
# 1. Copy deploy/ to the server (git is not installed yet on a bare box)
scp -r deploy forge@SERVER:/home/forge/siteforge-bootstrap

# 2. Configure and provision, as root
cp deploy.env.example deploy.env && $EDITOR deploy.env
bash /home/forge/siteforge-bootstrap/provision.sh

# 3. Deploy key -> GitHub (read-only)
sudo -u forge ssh-keygen -t ed25519 -N '' -f /home/forge/.ssh/id_ed25519
cat /home/forge/.ssh/id_ed25519.pub

# 4. Secrets
$EDITOR /var/www/builder/shared/.env       # ANTHROPIC_API_KEY, HostAfrica

# 5. First release
sudo -u forge bash /home/forge/siteforge-bootstrap/deploy.sh
sudo -u forge /usr/bin/php8.4 /var/www/builder/current/artisan key:generate --force
```

After the first release, always use the copy inside the release:
`/var/www/builder/current/deploy/deploy.sh`.

---

## Day-to-day

```bash
# Deploy the configured branch
bash /var/www/builder/current/deploy/deploy.sh

# Deploy a specific tag, branch or sha
REF=v1.2.0 bash /var/www/builder/current/deploy/deploy.sh

# Build everything but do not activate (useful before a risky release)
DRY_RUN=1 bash /var/www/builder/current/deploy/deploy.sh

# Skip migrations
SKIP_MIGRATE=1 bash /var/www/builder/current/deploy/deploy.sh

# What is on disk, and what is live?
bash /var/www/builder/current/deploy/rollback.sh --list

# Go back one release
bash /var/www/builder/current/deploy/rollback.sh
```

---

## What makes a deploy safe

- The release is built in a fresh directory; `current` is untouched until the
  build succeeds.
- A boot check (`artisan about`) runs before activation, so a release that
  cannot read its own config never goes live.
- Activation is `ln` + `mv -T`, which is atomic — an in-flight request resolves
  either the old release or the new one, never a missing symlink.
- A failed build is deleted and the previous release keeps serving.
- `flock` prevents two deploys from running at once.
- Old releases keep their `vendor/`, so rollback is a symlink flip, not a rebuild.

**Not covered:** migrations are never reversed. A destructive migration needs a
database restore, not a rollback — see below.

---

## Backups

`provision.sh` installs a nightly dump at 02:30 UTC (`/etc/cron.d/siteforge-backup`),
keeping 14 days in `/var/backups/siteforge`. It reads its credentials from the app's
own `shared/.env`, so the two can never drift apart, and it verifies each dump is a
valid gzip stream that reached its own completion marker **before** rotating anything
out — a truncated dump is worse than no dump, because it looks like a backup.

```bash
sudo /usr/local/bin/siteforge-backup          # run one now
tail -20 /var/log/siteforge-backup.log        # what the cron job did
ls -la /var/backups/siteforge/
```

Restore:

```bash
zcat /var/backups/siteforge/siteforge-YYYYMMDD-HHMMSS.sql.gz | \
  mysql -u siteforge -p siteforge
```

This is the **entire** safety net for schema changes — the box has no binary log and
no point-in-time recovery, so the most you can lose is one day. Before deploying a
destructive migration, take a dump first and don't rely on the nightly one.

---

## Layout on the server

```text
/var/www/builder/
    releases/
        20260803-104200-a1b2c3d/    a full checkout + vendor/
        20260803-091500-9f8e7d6/
    shared/
        .env                        real environment file, mode 600
        deploy.env                  deployment config
        storage/                    Laravel storage, survives releases
        repo.git/                   bare mirror
        deploy.lock
    current -> releases/20260803-104200-a1b2c3d

/srv/websites/                      published customer sites
/srv/websites/domains/<host>        symlink -> the slug directory
```

---

## Services

| Service | Bind | Role |
|---------|------|------|
| caddy | `:80`, `:443` | TLS edge, on-demand certs for customer domains |
| nginx | `127.0.0.1:8080` | Origin — app + published static sites |
| php8.4-fpm | `/run/php/siteforge.sock` | `siteforge` pool, runs as the deploy user |
| mysql | `127.0.0.1:3306` | Application database |
| redis | `127.0.0.1:6379` | Queues (Horizon), cache |
| supervisor | — | `siteforge-horizon`, `siteforge-scheduler` |

```bash
sudo supervisorctl status
journalctl -u caddy -f
tail -f /var/www/builder/shared/storage/logs/laravel.log
```
