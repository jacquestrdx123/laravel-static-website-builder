# SiteForge hosting runbook — Caddy TLS + Nginx origin

Single-VPS setup: **Caddy** terminates HTTPS (on-demand certs); **Nginx** serves the Laravel app and published static sites on localhost only.

Related plan: [caddy-nginx-provisioner-plan.md](./caddy-nginx-provisioner-plan.md)

---

## Traffic flow

```text
Client :80/:443
    → Caddy (public)
         • ACME + on-demand TLS (ask → Laravel /caddy/allowed)
         • reverse_proxy → 127.0.0.1:8080
    → Nginx (loopback only)
         • app.example.com          → /var/www/builder/public + PHP-FPM
         • 127.0.0.1 / localhost    → Laravel (Caddy ask + health)
         • {slug}.sites.example.com → /srv/websites/{slug}
         • other Host               → /srv/websites/domains/{host}
```

PHP never reloads Nginx. Publish only writes files and domain symlinks via `PublishedSiteHost`.

---

## Paths & users

| Path | Purpose | Owner |
|------|---------|--------|
| `/var/www/builder` | Releases, shared state, `current` symlink | `forge`:`forge` |
| `/var/www/builder/current/public` | Laravel public | same |
| `/var/www/builder/shared/.env` | Real environment file (mode 600) | `forge`:`forge` |
| `/srv/websites` | `SITES_PUBLISH_PATH` | `forge`:`forge` |
| `/srv/websites/{slug}` | Live site files | `forge` |
| `/srv/websites/domains/{host}` | Symlink → slug dir | `forge` |
| `/etc/caddy/Caddyfile` | Edge config (from `deploy/caddy.edge.Caddyfile`) | `root` |
| `/etc/nginx/sites-available/siteforge-origin` | Origin config (from `deploy/nginx.origin.conf`) | `root` |

One OS user, `forge`, owns the whole chain: it runs the Nginx workers, the
PHP-FPM `siteforge` pool, the Horizon/scheduler processes, and the deploy
scripts. Its sudo rights are scoped by `/etc/sudoers.d/siteforge-deploy` to
exactly the reloads a deploy needs — nothing more.

---

## DNS

| Record | Target |
|--------|--------|
| `A` / `AAAA` `app.example.com` | VPS public IP |
| `A` / `AAAA` `*.sites.example.com` | Same IP (wildcard for free customer hosts) |
| Customer domain `A` / `CNAME` | Same IP |

TLS: **on-demand only** for customer hostnames. The app hostname uses Caddy’s normal automatic HTTPS (named site block, not on-demand).

---

## Environment contract

Set these on the VPS `.env` (see also `config/sites.php`, `config/hosting.php`):

| Variable | Production value | Notes |
|----------|------------------|--------|
| `APP_URL` | `https://app.example.com` | Builder origin |
| `SITES_DOMAIN` | `sites.example.com` | Free zone: `{slug}.{SITES_DOMAIN}` |
| `SITES_PUBLISH_PATH` | `/srv/websites` | Must match Nginx roots |
| `HOSTING_DRIVER` | `filesystem` | Phase 1–2; `null` locally |
| `CADDY_ASK_URL` | `http://127.0.0.1:8080/caddy/allowed` | Documented for ops; wired in Caddyfile |
| `NGINX_CUSTOMERS_PATH` | *(unused Phase 1)* | Reserved for optional snippet driver |
| `CDN_BASE_URL` | `https://app.example.com` | Product image CDN on the app host |

Replace placeholders in Caddy/Nginx configs before install (`APP_HOST`, `SITES_DOMAIN`, email).

---

## Config files in this repo

| File | Role |
|------|------|
| `deploy/deploy.env.example` | Config template — hostnames, repo, DB (copy to `deploy.env`) |
| `deploy/provision.sh` | Idempotent server provisioning (root, one command) |
| `deploy/deploy.sh` | Atomic release deploy |
| `deploy/rollback.sh` | Flip `current` back to a previous release |
| `deploy/caddy.edge.Caddyfile` | Public TLS edge → proxy to Nginx |
| `deploy/nginx.origin.conf` | Localhost:8080 app + static sites |
| `deploy/php-fpm.pool.conf` | Dedicated `siteforge` FPM pool |
| `deploy/supervisor/siteforge.conf` | Horizon + scheduler |

---

## Release layout

```text
/var/www/builder/
    releases/20260803-104200-a1b2c3d/   built in full, then activated
    shared/.env                          symlinked into every release
    shared/storage/                      symlinked into every release
    shared/deploy.env                    deployment config
    shared/repo.git/                     bare mirror for cheap fetches
    current -> releases/20260803-104200-a1b2c3d
```

`current` only moves after the new release is fully built and passes a boot
check, so a failed deploy cannot take the site down.

---

## Bootstrap (first bring-up)

1. Point DNS at the VPS: `A app`, `A sites`, `A *.sites`.
2. Copy `deploy/` to the server and run, as root:

```bash
cp deploy/deploy.env.example deploy/deploy.env
$EDITOR deploy/deploy.env          # APP_HOST, SITES_DOMAIN, ACME_EMAIL, REPO_URL
bash deploy/provision.sh
```

`provision.sh` installs PHP/Composer/Redis/Supervisor/Caddy/Chromium, repairs
any broken apt keyrings, writes the Nginx + Caddy + FPM configs, creates the
MySQL database, and generates `/var/www/builder/shared/.env`.

3. Add the deploy key to GitHub (read-only) and fill in `ANTHROPIC_API_KEY`:

```bash
sudo -u forge ssh-keygen -t ed25519 -N '' -f /home/forge/.ssh/id_ed25519
cat /home/forge/.ssh/id_ed25519.pub          # → GitHub → Settings → Deploy keys
$EDITOR /var/www/builder/shared/.env
```

4. Ship the first release, then generate the app key:

```bash
sudo -u forge bash deploy/deploy.sh
sudo -u forge /usr/bin/php8.4 /var/www/builder/current/artisan key:generate --force
```

5. Smoke tests (below).

---

## Deploy (subsequent releases)

As the deploy user:

```bash
bash /var/www/builder/current/deploy/deploy.sh      # deploy REPO_BRANCH
REF=v1.2.0 bash .../deploy.sh                        # a specific tag or sha
DRY_RUN=1 bash .../deploy.sh                         # build without activating
```

Roll back:

```bash
bash /var/www/builder/current/deploy/rollback.sh --list
bash /var/www/builder/current/deploy/rollback.sh     # previous release
```

Rollback flips the symlink only — **database migrations are not reversed**.

---

## Smoke tests

```bash
# App (via public HTTPS)
curl -sI "https://app.example.com/up" | head -1

# Ask endpoint on loopback (what Caddy uses)
curl -sI "http://127.0.0.1:8080/caddy/allowed?domain=unknown.example" | head -1
# expect 404

# After publishing a site with slug "demo":
curl -sI "http://127.0.0.1:8080/" -H "Host: demo.sites.example.com" | head -1
# expect 200

curl -s "http://127.0.0.1:8080/caddy/allowed?domain=demo.sites.example.com"
# expect ok / 200
```

Custom domain: create DNS → publish → link domain in app → confirm symlink:

```bash
ls -la /srv/websites/domains/
curl -sI "https://www.customer.com/" | head -1
```

---

## Permissions checklist

Nginx, the PHP-FPM `siteforge` pool and the deploy user are all the **same
user** (`forge`). That is deliberate: publishing a site happens inside a web
request, so PHP has to write `/srv/websites` directly, and a single owner
removes the group-permission juggling entirely.

```bash
sudo chown -R forge:forge /srv/websites /var/www/builder
sudo chmod 755 /srv/websites /srv/websites/domains
grep '^user' /etc/nginx/nginx.conf              # -> user forge;
grep -E '^(user|group)' /etc/php/8.4/fpm/pool.d/siteforge.conf
```

`provision.sh` sets all of this up; the commands above are for verification.

---

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Cert not issuing / TLS handshake fails | `/caddy/allowed` returns 404 (site not published); DNS not pointing here; ask URL unreachable from Caddy |
| 404 on `{slug}.sites…` | Missing `/srv/websites/{slug}`; wrong `SITES_DOMAIN` in Nginx regex vs `.env` |
| 404 on custom domain | Missing symlink under `/srv/websites/domains/{host}`; Host header mismatch (www vs apex) |
| Ask always 502 | Nginx/PHP down; loopback server block missing; wrong `root` for Laravel |
| App 502 | PHP-FPM socket missing — pool listens on `/run/php/siteforge.sock`; check `systemctl status php8.4-fpm` |
| App serves the previous release | `php8.4-fpm` was not reloaded after the symlink flip (realpath cache) |
| Publish fails with permission denied | `/srv/websites` not owned by `forge`, or the FPM pool is running as another user |
| `apt-get update` fails, PHP will not install | Empty apt keyring in `/usr/share/keyrings` — re-run `provision.sh`, which repairs them |

Caddy logs: `journalctl -u caddy -f`  
Nginx logs: `/var/log/nginx/error.log`  
Laravel: `storage/logs/laravel.log`

---

## Security notes

- Nginx listens on **127.0.0.1:8080 only** — never bind the origin to a public interface.
- Do not grant PHP full sudo. Phase 1 needs none.
- `/caddy/allowed` is reachable on the public app Host as well; it only returns 200 for published sites. Prefer keeping the Caddy `ask` URL on loopback.
- Never put secrets in the Caddyfile; only hostname placeholders and email.
