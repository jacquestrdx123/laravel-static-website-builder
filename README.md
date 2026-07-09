# SiteForge — AI Static Website Builder

A Laravel SaaS where customers upload photos, flip a few toggles, and Claude
generates a complete static website (pure HTML/CSS/vanilla JS). Customers
preview the result and publish it to their own subdomain, served per-customer
by Caddy with automatic HTTPS.

## How it works

```
register → buy AI credits → describe business + upload images + choose toggles
        → queued job calls the Claude API (vision + structured output)
        → static site written to storage/app/public/sites/{slug}
        → owner previews in-app (served directly at /storage/sites/{slug}/)
        → publish copies the site to SITES_PUBLISH_PATH
        → Caddy serves {slug}.sites.yourdomain.com (and custom domains)
```

### The moving parts

| Piece | Where |
|---|---|
| AI generation (Claude API, streaming + structured JSON output) | `app/Services/WebsiteGenerator.php` |
| Queued generation job (refunds credit on failure) | `app/Jobs/GenerateWebsiteJob.php` |
| Builder wizard (uploads + toggles) | `app/Http/Controllers/WebsiteController.php`, `resources/views/websites/create.blade.php` |
| Preview of generated sites | Served statically from the public disk (`/storage/sites/{slug}/index.html` via `php artisan storage:link`); URL built by `Website::previewUrl()` |
| Publish / unpublish to the Caddy web root | `app/Http/Controllers/PublishController.php` |
| Credits ledger + stubbed checkout | `app/Http/Controllers/BillingController.php`, `App\Models\User::spendCredits()` |
| Caddy `on_demand_tls` ask endpoint | `app/Http/Controllers/CaddyController.php` (`GET /caddy/allowed`) |
| Caddy server config | `deploy/Caddyfile` |

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link   # previews are served from the public disk

# Required for generation:
#   ANTHROPIC_API_KEY=sk-ant-...   (in .env)

php artisan serve          # the app
php artisan queue:work     # processes generation jobs (required!)
```

Sign up (you get 1 free credit), create a website, and watch the queue worker
run the generation. Without a queue worker jobs sit in the `jobs` table forever.

### Environment variables

| Var | Purpose | Default |
|---|---|---|
| `ANTHROPIC_API_KEY` | Claude API key used for generation | — (required) |
| `ANTHROPIC_MODEL` | Model used for site generation | `claude-opus-4-8` |
| `ANTHROPIC_MAX_TOKENS` | Output budget per generation | `64000` |
| `SITES_DOMAIN` | Wildcard zone for published sites (`{slug}.SITES_DOMAIN`) | `sites.localhost` |
| `SITES_PUBLISH_PATH` | Directory Caddy serves published sites from | `storage/app/published` |
| `SITES_GENERATION_COST` | Credits per generation | `1` |
| `SITES_MAX_IMAGES` | Max uploads per website | `10` |

## Production hosting with Caddy

`deploy/Caddyfile` contains a full working config. The shape:

1. **The app** is served on `app.example.com` via `php_fastcgi`.
2. **Published sites** live in `/srv/websites/{slug}` (`SITES_PUBLISH_PATH`).
3. A single `https://` site block serves *all* customer hostnames:
   - `{slug}.sites.example.com` maps to `/srv/websites/{slug}` via `host_regexp`.
   - Custom domains map to `/srv/websites/domains/{host}` (symlink per domain).
4. **on-demand TLS**: before issuing a certificate for any hostname, Caddy asks
   the app (`GET /caddy/allowed?domain=...`), which returns 200 only for
   published sites. That's the whole "custom domain" security story — no
   certificates for hostnames that aren't yours.

DNS required: `A app.example.com` and a wildcard `A *.sites.example.com`
pointing at the server.

## Laravel Forge deployment

This app has **no frontend build** — all styling is inline in the Blade layout.
A stub `package.json` is included so Forge's default `npm ci && npm run build`
steps succeed as no-ops if you haven't customised the deployment script yet.

Recommended deployment script (also in `deploy/forge-deploy.sh`):

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

Also configure in Forge:

- **Environment**: `ANTHROPIC_API_KEY`, `SITES_DOMAIN`, `SITES_PUBLISH_PATH`.
- **Queues**: add a worker on the `database` connection — generations are
  queued jobs and never run without one.
- **Branch**: make sure the site deploys the branch that actually contains
  this code.

## Credits & payments

Credits are integer balances on the user with a `credit_transactions` ledger.
`User::spendCredits()` is atomic (guarded decrement) so concurrent generations
can't double-spend. Failed generations are automatically refunded.

**Payments are stubbed**: the billing page "purchase" adds credits immediately.
To go live, replace `BillingController::purchase()` with a real gateway flow
(Stripe / Paystack / PayFast): create checkout session → redirect → verify in
the webhook → `addCredits()` from the webhook handler. Publishing is free right
now; gate `PublishController::store()` behind a hosting subscription when
billing lands.

## How generation works (Claude API)

`WebsiteGenerator` sends one streaming request to the Messages API:

- **Model**: `claude-opus-4-8` with adaptive thinking.
- **Vision**: every uploaded photo is passed as a base64 image block, so the
  model designs around the actual images. Uploads are also copied into the
  site's `assets/` directory, and the model is told each file's path.
- **Structured output**: `output_config.format` with a JSON schema
  (`{files: [{path, content}]}`) guarantees parseable output.
- **Streaming**: long generations run for minutes; streaming avoids HTTP
  timeouts. Refusals and `max_tokens` truncation are handled with clear
  user-facing errors and an automatic credit refund.
- Model-written file paths are sanitised before writing (no traversal, must
  stay inside the site directory).

## Tests

```bash
php artisan test
```

Covers: registration + welcome credit, credit spend/insufficient-credit paths,
job queuing, preview authorisation + path-traversal defence, the Caddy ask
endpoint, and the billing stub.

## Roadmap / TODO

- [ ] Real payment gateway for credits and hosting subscriptions
- [ ] Custom-domain self-service (currently manual symlink + `custom_domain` column)
- [ ] Post-generation chat edits ("make the header darker") — cheap follow-up turns reusing the conversation
- [ ] Email verification + password reset
- [ ] Per-user storage quotas and image optimisation (thumbnails / WebP)
