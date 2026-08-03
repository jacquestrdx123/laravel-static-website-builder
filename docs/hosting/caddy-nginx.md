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
| `/var/www/builder` | App release (current symlink) | `deploy`:`www-data` |
| `/var/www/builder/current/public` | Laravel public | same |
| `/srv/websites` | `SITES_PUBLISH_PATH` | `www-data`:`www-data` (deploy group write) |
| `/srv/websites/{slug}` | Live site files | `www-data` |
| `/srv/websites/domains/{host}` | Symlink → slug dir | `www-data` |
| `/etc/caddy/Caddyfile` | Edge config (from `deploy/caddy.edge.Caddyfile`) | `root` |
| `/etc/nginx/sites-available/siteforge-origin` | Origin config (from `deploy/nginx.origin.conf`) | `root` |

Suggested OS users:

- `deploy` — git pull / `deploy/deploy.sh`
- `www-data` — PHP-FPM pool user (reads app + writes `/srv/websites`)

`deploy` should be in group `www-data` with group-writable `/srv/websites` and storage dirs.

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
| `deploy/caddy.edge.Caddyfile` | Public TLS edge → proxy to Nginx |
| `deploy/nginx.origin.conf` | Localhost:8080 app + static sites |
| `deploy/bootstrap-vps.sh` | Idempotent Ubuntu package + dir setup |
| `deploy/deploy.sh` | Release deploy (no Forge) |
| `deploy/supervisor/siteforge.conf` | Horizon + scheduler |
| `deploy/Caddyfile` | **Legacy** — Caddy served files itself; prefer edge+origin |
| `deploy/nginx.conf.example` | **Legacy** — public TLS Nginx; prefer origin |

---

## Bootstrap (first bring-up)

1. Point DNS at the VPS (or use staging hosts).
2. As root: copy repo (or clone) and run:

```bash
export APP_HOST=app.example.com
export SITES_DOMAIN=sites.example.com
export CADDY_EMAIL=admin@example.com
export DEPLOY_USER=deploy
sudo -E bash deploy/bootstrap-vps.sh
```

3. Clone/release the app into `/var/www/builder`, copy `.env`, `composer install`, `artisan key:generate`, `migrate`, `storage:link`.
4. `sudo systemctl reload nginx && sudo systemctl reload caddy`
5. Smoke tests (below).

---

## Deploy (subsequent releases)

As `deploy`:

```bash
cd /var/www/builder
./deploy/deploy.sh   # or: bash /path/to/repo/deploy/deploy.sh
```

Restarts Supervisor programs after migrate/optimize.

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

```bash
sudo mkdir -p /srv/websites/domains
sudo chown -R www-data:www-data /srv/websites
sudo chmod 2775 /srv/websites /srv/websites/domains
# deploy user can write published trees via www-data group
sudo usermod -aG www-data deploy
```

PHP-FPM must run as `www-data` (default) so publish from the web request can write `/srv/websites`.

---

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Cert not issuing / TLS handshake fails | `/caddy/allowed` returns 404 (site not published); DNS not pointing here; ask URL unreachable from Caddy |
| 404 on `{slug}.sites…` | Missing `/srv/websites/{slug}`; wrong `SITES_DOMAIN` in Nginx regex vs `.env` |
| 404 on custom domain | Missing symlink under `/srv/websites/domains/{host}`; Host header mismatch (www vs apex) |
| Ask always 502 | Nginx/PHP down; loopback server block missing; wrong `root` for Laravel |
| App 502 | PHP-FPM socket path mismatch (check `php8.3-fpm.sock` vs installed version) |
| Publish fails with permission denied | `/srv/websites` not writable by `www-data` |

Caddy logs: `journalctl -u caddy -f`  
Nginx logs: `/var/log/nginx/error.log`  
Laravel: `storage/logs/laravel.log`

---

## Security notes

- Nginx listens on **127.0.0.1:8080 only** — never bind the origin to a public interface.
- Do not grant PHP full sudo. Phase 1 needs none.
- `/caddy/allowed` is reachable on the public app Host as well; it only returns 200 for published sites. Prefer keeping the Caddy `ask` URL on loopback.
- Never put secrets in the Caddyfile; only hostname placeholders and email.
