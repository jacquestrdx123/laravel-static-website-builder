# Plan: Caddy TLS + Nginx static hosting (without Forge)

**Goal:** Run SiteForge on a self-managed VPS where **Caddy terminates SSL** and **Nginx serves websites** (and the Laravel app), with a small app-side provisioner that keeps publish/domain state in sync.

**Status:** Phase 0–1 assets in repo (runbook, edge/origin configs, bootstrap + deploy scripts). Staging VPS cutover still pending.  
**Depends on existing code:** `PublishedSiteHost`, `CaddyController` (`/caddy/allowed`), `PublishController` / Livewire publish, domain link/unlink

---

## 1. Principles

1. **Caddy owns HTTPS only** — on-demand certs for customer hosts; managed cert for the app hostname.
2. **Nginx owns HTTP origin** — static customer sites + PHP-FPM for the builder app, listening on localhost only.
3. **Laravel owns “is this hostname allowed?”** — keep `/caddy/allowed`.
4. **Filesystem is the source of truth for site files** — `/srv/websites/{slug}` + `/srv/websites/domains/{host}` symlinks (already implemented).
5. **Prefer zero Nginx reloads** for Phase 1 (catch-all + Host-based roots). Add per-domain snippets only if needed later.

---

## 2. Target traffic flow

```text
Client
  │ :443 / :80
  ▼
Caddy (public)
  • TLS (on_demand / wildcard)
  • Ask Laravel: GET /caddy/allowed?domain=
  • reverse_proxy → 127.0.0.1:8080
  ▼
Nginx (localhost:8080)
  • app.example.com     → /var/www/builder/public + PHP-FPM
  • {slug}.sites.*      → /srv/websites/{slug}
  • any other Host      → /srv/websites/domains/$host  (symlink)
```

Laravel queue workers, scheduler, and Redis stay on the **same single VPS** — unchanged from today’s mental model.

---

## 3. Phased delivery

### Phase 0 — Docs & config contracts (½ day)

**Deliverables**

- [x] `docs/hosting/caddy-nginx.md` runbook (DNS, ports, paths, permissions)
- [x] Env/config keys documented:
  - `SITES_DOMAIN`
  - `SITES_PUBLISH_PATH` (prod: `/srv/websites`)
  - `HOSTING_DRIVER=filesystem|nginx_snippets|null`
  - `NGINX_CUSTOMERS_PATH` (Phase 3 reserved)
  - `CADDY_ASK_URL` (internal, default `http://127.0.0.1:8080/caddy/allowed`)
  - `config/hosting.php` + `.env.example`

**Exit criteria:** Engineer can stand up a blank VPS using the runbook without guessing paths.

---

### Phase 1 — Edge + origin configs (no per-site Nginx writes) (1–2 days)

**Goal:** Replace Forge with static server configs. App publish/link behaviour stays as today.

#### 1A. Caddy edge (`deploy/caddy.edge.Caddyfile`)

- Listen `:80` / `:443`
- `on_demand_tls { ask ... }`
- Catch-all `https://` → `reverse_proxy 127.0.0.1:8080`
- Optional: dedicated block for `app.example.com` if you want app TLS separate (same proxy is fine)

#### 1B. Nginx origin (`deploy/nginx.origin.conf`)

Three server contexts on `127.0.0.1:8080`:

| `server_name` | Root / upstream |
|---------------|-----------------|
| `app.example.com` | Laravel `public/` + PHP-FPM |
| `~^(?<slug>...)\.sites\.example\.com$` | `/srv/websites/$slug` |
| default / catch-all | `/srv/websites/domains/$host` |

Security headers, `try_files`, deny dotfiles, sensible `client_max_body_size` for uploads on the app vhost only.

#### 1C. Bootstrap script (`deploy/bootstrap-vps.sh`)

Idempotent install for Ubuntu:

1. Nginx, Caddy, PHP-FPM, Redis, Supervisor, Cert tooling (none for Nginx)
2. Users/dirs: deploy user owns `/var/www/builder` and `/srv/websites`
3. Enable Caddy + Nginx units
4. Deploy hooks: `composer`, `artisan migrate`, `queue:work`, `schedule`

#### 1D. Deploy script

Retarget `deploy/forge-deploy.sh` → `deploy/deploy.sh` (no Forge vars):

- `git pull` / release dir
- `composer install --no-dev`
- `artisan migrate --force`
- `artisan optimize`
- `artisan storage:link`
- restart Supervisor queue workers

**Exit criteria:** One published site reachable at `https://{slug}.sites.example.com` and one custom domain with on-demand cert after DNS points at the box.

**App code changes in Phase 1:** minimal (config comments + maybe assert publish path permissions). Prefer **no** provisioner interface yet.

---

### Phase 2 — HostProvisioner abstraction (1 day)

**Goal:** Clean extension point after publish/domain events without changing behaviour.

```text
Publish / Unpublish / Domain link / Domain unlink
        │
        ▼
PublishedSiteHost (files + symlinks)     ← keep as-is
        │
        ▼
HostProvisioner::ensure*/remove*         ← new
```

#### Types

```php
interface HostProvisioner {
    public function ensurePublished(Website $website): void;
    public function removePublished(Website $website): void;
    public function ensureCustomDomain(Website $website, string $hostname): void;
    public function removeCustomDomain(string $hostname): void;
}
```

#### Drivers

| Driver | When |
|--------|------|
| `NullHostProvisioner` | Local / tests |
| `FilesystemHostProvisioner` | Phase 1 prod (no-op beyond logging; files already done by `PublishedSiteHost`) |
| `NginxSnippetHostProvisioner` | Phase 3 |

Wire via `config/hosting.php` + container binding.

Call sites:

- `PublishController` / Livewire `Show::publish` / `unpublish`
- `DomainController::link` / `unlink`
- Website `destroy` (cleanup)

**Exit criteria:** Same runtime behaviour; tests swap to `NullHostProvisioner`.

---

### Phase 3 — Optional Nginx snippet provisioner (1–2 days)

Only if catch-all is insufficient (per-site redirects, basic auth, different roots, WAF rules).

#### Behaviour

1. Render Blade/stub template → `/etc/nginx/sites-available/customers/{host}.conf`
2. Symlink into `sites-enabled/customers/`
3. `sudo nginx -t`
4. On success → `sudo systemctl reload nginx`
5. On failure → remove new snippet, log, surface error to admin

#### Safety

- **Do not give PHP full sudo.** If snippets are ever needed, use a **narrow sudoers rule** so the PHP-FPM user may run only:
  - `/usr/sbin/nginx -t`
  - `/bin/systemctl reload nginx`
- Filename sanitisation: hostnames only (`[a-z0-9.-]+`)
- Never write outside the customers Nginx directory
- Prefer skipping this phase: Phase 1 catch-all needs **zero** sudo from PHP

**Exit criteria:** Linking a domain writes a conf, reload succeeds; unlink removes conf and reloads; failed `nginx -t` does not take down other sites.

---

### Phase 4 — Observability & ops (½–1 day)

- [ ] Health checks: Caddy `/`, Nginx localhost, Laravel `/up`, `/caddy/allowed` smoke
- [ ] Log shipping: Caddy access, Nginx access/error, Laravel
- [ ] Alerts: disk on `/srv/websites`, queue depth, failed provisioner jobs
- [ ] Backup: DB + `/srv/websites` + Caddy cert storage
- [ ] Runbook: “cert not issuing”, “404 on custom domain”, “nginx -t failed”

---

## 4. App integration map (what already exists)

| Concern | Current owner | Plan |
|---------|---------------|------|
| Copy preview → live tree | `PublishedSiteHost::publish` | Keep |
| Custom domain symlink | `PublishedSiteHost::syncCustomDomainSymlink` | Keep (Phase 1 truth) |
| TLS allowlist | `CaddyController@allowed` | Keep; ensure reachable from Caddy on loopback |
| Publish UI/API | Livewire `Show` + `PublishController` | Call provisioner after host |
| Domain link | `DomainController::link/unlink` | Call provisioner after symlink |

No change to product catalog, credits, or AI generation for this workstream.

---

## 5. DNS & TLS checklist

| Record | Purpose |
|--------|---------|
| `A/AAAA app.example.com` | Builder |
| `A/AAAA *.sites.example.com` | Free customer hosts |
| Customer `A/AAAA` or `CNAME` | Custom domains → same IP |

TLS modes:

1. **On-demand** for arbitrary customer hostnames (uses `/caddy/allowed`)
2. **Wildcard** for `*.sites.example.com` via DNS provider plugin (recommended for scale; fewer issuance races)

---

## 6. Security constraints

- Nginx bound to `127.0.0.1:8080` only (not public)
- Caddy is the only public listener on 80/443
- Publish path not web-writable by random users; deploy user + php-fpm pool user carefully scoped
- `/caddy/allowed` must not be publicly abuseable for scanning if exposed — prefer loopback-only ask URL; if public, rate-limit and keep 4xx for unknown hosts
- Path traversal: never accept raw host strings into file paths without normalisation (lowercase, strip ports, reject `..`)

---

## 7. Testing plan

| Layer | Tests |
|-------|-------|
| Unit | Hostname sanitisation; provisioner no-op; snippet template render |
| Feature | Publish creates `/srv/.../{slug}`; link creates domain symlink; `/caddy/allowed` 200/404 matrix |
| Integration (staging VPS) | Real Caddy on-demand cert for a test domain; Nginx serves symlink; unpublish → ask returns 404 → cert not re-issued |
| Failure | Corrupt Nginx snippet → reload skipped; previous sites still up |

---

## 8. Suggested implementation order (tickets)

1. **HOST-01** Write `docs/hosting/caddy-nginx.md` + env contract  
2. **HOST-02** Add `deploy/caddy.edge.Caddyfile` + `deploy/nginx.origin.conf`  
3. **HOST-03** Add `deploy/bootstrap-vps.sh` + `deploy/deploy.sh`  
4. **HOST-04** Staging VPS bring-up; migrate one real site off Forge  
5. **HOST-05** Introduce `HostProvisioner` interface + null/filesystem drivers; wire publish/link  
6. **HOST-06** (Optional) `NginxSnippetHostProvisioner` + sudoers/host agent  
7. **HOST-07** Monitoring, backups, cutover checklist  

---

## 9. Explicit non-goals (this plan)

- Multi-region CDN / object-storage origins (later)
- Replacing HostAfrica domain registrar flow
- Moving AI generation off the app server
- Per-tenant containers/Kubernetes (overkill for current scale)

---

## 10. Recommendation

**Build Phase 1 + Phase 2 first.**  

Phase 1 gets you off Forge with Caddy SSL + Nginx static serving.  
Phase 2 keeps the code clean.  
Skip Phase 3 until a concrete Nginx-per-domain requirement appears — your symlink model already covers custom domains without reloads.

---

## 11. Decisions

| Decision | Choice |
|----------|--------|
| Topology | **Single VPS** — Caddy, Nginx, PHP-FPM, Redis, queue workers, and `/srv/websites` on one box. |
| TLS for customer hosts | **On-demand only** (Caddy `ask` → `/caddy/allowed`). No wildcard DNS plugin. |
| App behind same Caddy→Nginx path? | **Yes** — builder and customer sites share the edge. |
| Nginx reloads / Phase 3 | **Not needed for Phase 1–2.** Catch-all + domain symlinks mean PHP never touches Nginx. If Phase 3 is ever required: **limited sudoers** (only `nginx -t` + `systemctl reload nginx`), not full sudo and not a separate host agent. |

Implementation can start at **HOST-01 / HOST-02**.
