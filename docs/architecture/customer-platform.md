# Customer Platform Architecture

**Status:** As implemented (July 2026)  
**Audience:** Engineering, product  
**Scope:** Customer-facing functionality — data storage, generation, retrieval, and display

This document describes what the platform offers to customers today, how data is stored and moved through the system, and how that maps to the planned per-website product/media/marketing model.

---

## Related standards

- **[Product catalog & asset CDN](../standards/product-catalog.md)** — MySQL catalog JSON, CDN URLs, HTML sync contract, and AI generation rules.

---

## Table of contents

1. [Overview](#overview)
2. [Architecture diagram](#architecture-diagram)
3. [Storage layers](#storage-layers)
4. [Authentication and billing](#authentication-and-billing)
5. [Websites and AI generation](#websites-and-ai-generation)
6. [Photos and media](#photos-and-media)
7. [Preview, publish, and hosting](#preview-publish-and-hosting)
8. [Manual content editing](#manual-content-editing)
9. [Newsletters and subscribers](#newsletters-and-subscribers)
10. [Marketing posters](#marketing-posters)
11. [Domain management](#domain-management)
12. [Background jobs](#background-jobs)
13. [Per-website content vault](#per-website-content-vault)
14. [Admin panel (Filament)](#admin-panel-filament)
15. [Configuration reference](#configuration-reference)
16. [Gap analysis and roadmap alignment](#gap-analysis-and-roadmap-alignment)

---

## Overview

The platform is a Laravel application that lets customers:

1. Register and purchase **AI credits**
2. Create **websites** from a brief and uploaded photos (AI-generated static HTML/CSS/JS)
3. **Preview** and **publish** sites to a wildcard subdomain or custom domain
4. **Edit live content** (offerings, tagline, contact email) with an active subscription
5. Generate **newsletters** and **posters** from website context
6. Manage **newsletter subscribers** and send campaigns
7. Register and manage **domains** via HostAfrica

Two storage tiers underpin the design:

| Tier | Technology | Purpose |
|------|------------|---------|
| **Operational** | Laravel DB (SQLite/MySQL) | Users, websites, images metadata, subscribers, subscriptions, domains, credits |
| **Artifacts** | Per-website filesystem vault | Newsletters, posters, product change snapshots (SQLite index + files) |

Generated customer websites are **static files** on disk — not rendered by Laravel at runtime.

---

## Architecture diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Customer (authenticated)                        │
│   Blade UI: dashboard, website CRUD, content edit, marketing, billing   │
└───────────────────────────────┬─────────────────────────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        ▼                       ▼                       ▼
┌───────────────┐     ┌─────────────────┐     ┌──────────────────┐
│  Laravel DB   │     │  Queue workers  │     │   Filesystem     │
│               │     │                 │     │                  │
│ users         │     │ GenerateWebsite │     │ uploads/         │
│ websites      │◄───►│ GenerateNewsletter     │ sites/{slug}/    │
│ website_images│     │ GeneratePoster  │     │ published/       │
│ subscribers   │     │ SendNewsletter  │     │ website-data/    │
│ subscriptions │     └────────┬────────┘     └──────────────────┘
│ domains       │              │
│ credits       │              ▼
└───────────────┘     ┌─────────────────┐
                      │ Anthropic API   │
                      │ (Claude)        │
                      └─────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                         Public visitors                                 │
│   Published static site (Caddy)  │  POST /sites/{slug}/newsletter/...   │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Storage layers

### 1. Laravel database

| Table | Scoped to website | Key fields / notes |
|-------|-------------------|-------------------|
| `users` | — | `ai_credits`, `is_admin` |
| `websites` | — | `slug`, `status`, `settings` (JSON), `custom_domain` |
| `website_images` | ✅ `website_id` | `path`, `type`, `sort`, `description`, `mime_type` |
| `newsletter_subscribers` | ✅ `website_id` | `email`, `status`, `unsubscribe_token` |
| `website_subscriptions` | ✅ `website_id` | `type`, `status`, `expires_at` |
| `credit_transactions` | — (user) | Ledger of credit spends/refunds |
| `domains` | Linked via `website_id` | Registrar state, nameservers, contacts |
| `domain_orders` | — | Order history for domain operations |

**Website settings JSON** holds the generation brief and business configuration:

```json
{
  "description": "...",
  "tagline": "...",
  "contact_email": "...",
  "site_type": "business|portfolio|restaurant|landing|personal|event",
  "sections": ["hero", "about", "contact"],
  "style": "minimal|bold|elegant|playful|corporate",
  "color_scheme": "light|dark|auto",
  "accent_color": "#336699",
  "features": ["smooth_scroll", "seo_meta"],
  "offering_type": "services|products",
  "offering_label": "Menu",
  "ai_elaborate_offerings": true,
  "offerings": [
    {
      "name": "Item name",
      "description": "...",
      "price": "R99",
      "image_id": 5
    }
  ],
  "extra_instructions": "..."
}
```

There is **no dedicated `products` table**. Offerings in `settings` are the current product/service catalog.

### 2. Private upload storage

| Path | Disk | Contents |
|------|------|----------|
| `uploads/{website_id}/*` | `local` | Original customer photo uploads |

Served to authenticated users only via `ContentController::image`.

### 3. Generated site preview

| Path | Disk | Contents |
|------|------|----------|
| `sites/{slug}/` | `public` | AI-generated static site (index.html, styles.css, script.js, assets/) |

Preview URL: `{APP_URL}/storage/sites/{slug}/index.html` (requires `storage:link`).

During generation, customer photos are copied from `uploads/` into `sites/{slug}/assets/image-{N}.{ext}`.

### 4. Published site storage

| Path | Contents |
|------|----------|
| `{SITES_PUBLISH_PATH}/{slug}/` | Live copy of the generated site (Caddy web root) |
| `{SITES_PUBLISH_PATH}/domains/{domain}/` | Symlink (or directory copy) pointing at the slug directory |

Live URL: `https://{slug}.{SITES_DOMAIN}` or `https://{custom_domain}`.

### 5. Per-website content vault

| Path | Contents |
|------|----------|
| `{WEBSITE_DATA_PATH}/{website_id}/vault.sqlite` | Index: product_snapshots, newsletters, posters |
| `{WEBSITE_DATA_PATH}/{website_id}/products/{uuid}/` | Change snapshots (before/after JSON, XML) |
| `{WEBSITE_DATA_PATH}/{website_id}/newsletters/{uuid}/` | Newsletter meta, HTML, plain text, XML |
| `{WEBSITE_DATA_PATH}/{website_id}/posters/{uuid}/` | Poster HTML, optional PNG, XML |
| `{WEBSITE_DATA_PATH}/{website_id}/manifest.json` | Aggregate counts |

**Not web-accessible.** Accessed only via `App\Services\WebsiteContentVault`.

---

## Authentication and billing

### Routes

| Method | Path | Controller | Purpose |
|--------|------|------------|---------|
| GET | `/register` | `AuthController@showRegister` | Registration form |
| POST | `/register` | `AuthController@register` | Create account + 1 welcome credit |
| GET/POST | `/login` | `AuthController` | Session login |
| POST | `/logout` | `AuthController@logout` | End session |
| GET | `/billing` | `BillingController@index` | Credit balance, packs, transaction history |
| POST | `/billing/purchase` | `BillingController@purchase` | **Stub:** add credits immediately |

### Credit model

- Balance stored on `users.ai_credits`
- All changes recorded in `credit_transactions`
- `User::spendCredits()` / `User::addCredits()` are atomic (DB transaction)
- Failed AI jobs refund credits automatically

### Credit costs (defaults)

| Action | Config key | Default |
|--------|------------|---------|
| Website generation / regeneration | `sites.generation_cost` | 1 |
| Newsletter generation | `sites.newsletter_generation_cost` | 2 |
| Poster generation | `sites.poster_generation_cost` | 3 |
| Domain operations | `DomainCreditPricing` | Dynamic from registrar API |

### Payment status

Credit packs and the manual-editing subscription are **stubbed** — no payment gateway integration yet. See TODO comments in `BillingController` and `WebsiteSubscriptionController`.

---

## Websites and AI generation

### Website lifecycle statuses

| Status | Meaning |
|--------|---------|
| `draft` | Created but not queued (unused in current create flow) |
| `queued` | Job dispatched, waiting for worker |
| `generating` | Worker running |
| `ready` | Generation succeeded; preview available |
| `failed` | Generation failed; credit refunded; `error` column set |
| `published` | Copied to live web root |

### Customer routes

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboard` | List websites |
| GET/POST | `/websites/create`, `/websites` | Creation form + store |
| GET | `/websites/{website}` | Show page: preview, publish, marketing links |
| GET | `/websites/{website}/status` | JSON poll while generating |
| POST | `/websites/{website}/regenerate` | Re-run AI with same settings (costs credits) |
| DELETE | `/websites/{website}` | Delete site, files, uploads, vault |

### Creation flow

1. Customer submits brief + photos (validated in `WebsiteController::store`)
2. Credit spent via `User::spendCredits()`
3. `Website` row created with `settings` JSON and `status = queued`
4. Photos stored as `WebsiteImage` rows; offering images linked via `image_id`
5. `GenerateWebsiteJob` dispatched

### Generation pipeline

**Service:** `App\Services\WebsiteGenerator`  
**Job:** `App\Jobs\GenerateWebsiteJob`  
**Prompt spec:** `resources/prompts/website-generator-system.md`

```
Website + images loaded
  → Delete existing site directory
  → Copy images to sites/{slug}/assets/
  → Stream Anthropic API request (system prompt cached, user brief + vision blocks)
  → Parse JSON response: { files: [{ path, content }] }
  → Write files to site directory (path sanitization, URL relativization)
  → Update website: status=ready, generated_at=now()
  → Record product snapshot in vault (source: website_generation)
```

On failure: status=`failed`, credit refunded, error message stored.

### Offerings as products

- Up to 12 offerings per website (`WebsiteController::MAX_OFFERINGS`)
- Types: `services` or `products` (also used for restaurant menus)
- AI must preserve exact names and prices; may elaborate descriptions when `ai_elaborate_offerings` is true
- Live offering text after generation lives in generated HTML, not necessarily identical to `settings`

### Editable content markers

The generation spec requires HTML annotations for post-generation editing:

| Marker | Purpose |
|--------|---------|
| `data-offering="N"` | Offering item container |
| `data-field="name\|description\|price\|image"` | Fields within an offering |
| `data-content="tagline"` | Tagline element |
| `data-content="contact-email"` | Contact email (including mailto links) |

These are consumed by `App\Services\SiteContentUpdater`.

---

## Photos and media

### Image types (`WebsiteImage`)

| Constant | Label | Typical use |
|----------|-------|-------------|
| `TYPE_LOGO` | Logo | Header branding |
| `TYPE_FAVICON` | Favicon | Browser tab icon |
| `TYPE_BANNER` | Banner | Hero section |
| `TYPE_GALLERY` | Gallery | Gallery section |
| `TYPE_PRODUCT` | Product | Offering card image |

### Limits and display

- **Max images per website:** `config('sites.max_images')` (default 10)
- **Upload formats:** JPEG, PNG, GIF, WebP (max 8 MB; favicon max 2 MB)
- **App preview:** `GET /websites/{website}/images/{image}` (auth required)
- **On generated site:** `assets/image-{sort+1}.{ext}`

### Videos

**Not implemented.** No model, migration, upload handler, storage path, or generation prompt support exists.

---

## Preview, publish, and hosting

### Preview

Available when `status` is `ready` or `published`.

- iframe on website show page
- Full-screen link to `Website::previewUrl()`

### Publish / unpublish

| Method | Path | Action |
|--------|------|--------|
| POST | `/websites/{website}/publish` | Copy preview → published path; set status |
| DELETE | `/websites/{website}/publish` | Remove published copy; revert to `ready` |

**Service:** `App\Services\PublishedSiteHost`

- `publish()`: copies `sites/{slug}/` → `{publish_path}/{slug}/`, syncs custom domain symlink
- `unpublish()`: deletes published directory and domain symlink

### Hosting infrastructure

- **Wildcard subdomain:** `{slug}.{SITES_DOMAIN}` (e.g. `my-shop.sites.example.com`)
- **Custom domain:** set via domain link flow; symlink at `published/domains/{domain}`
- **TLS gate:** `GET /caddy/allowed?domain=...` — Caddy `on_demand_tls` ask endpoint; returns 200 only for published sites

---

## Manual content editing

Gated by an active `manual_editing` subscription on the website.

### Routes

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/websites/{website}/content` | Edit form |
| POST | `/websites/{website}/content` | Save and apply to live HTML |
| GET | `/websites/{website}/images/{image}` | Serve uploaded image |

### Editable fields

- Tagline
- Contact email
- Offering type and label
- Offerings: name, description, price, image (existing or new upload)

### Update mechanism

**Service:** `App\Services\SiteContentUpdater`

1. Parse all HTML files in the site directory with DOMDocument
2. Rebuild offering sections from template (`data-offering` items)
3. Update `data-content` fields
4. If site is published, re-copy entire site directory to published path
5. Record vault snapshot (`source: content_edit`)

### Subscription

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/websites/{website}/subscription` | Show subscription status |
| POST | `/websites/{website}/subscription` | Purchase/extend (**stubbed**) |

**Model:** `WebsiteSubscription` — type `manual_editing`, default 1 year.

**Check:** `Website::hasActiveEditingSubscription()`

### Compatibility note

Sites generated before editable markers were introduced will not update until regenerated once. `SiteContentUpdater::supportsEditing()` detects marker presence.

---

## Newsletters and subscribers

### Newsletter storage

Newsletters are stored in the **per-website vault**, not the Laravel DB.

**Index:** `vault.sqlite` → `newsletters` table  
**Files:** `newsletters/{uuid}/content.html`, `content.txt`, `meta.json`, etc.

### Customer routes (authenticated)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/websites/{website}/newsletters` | List campaigns |
| GET | `/websites/{website}/newsletters/create` | Generation form |
| POST | `/websites/{website}/newsletters` | Queue generation (spends credits) |
| GET | `/websites/{website}/newsletters/{uuid}` | Preview HTML |
| POST | `/websites/{website}/newsletters/{uuid}/send` | Queue send to all subscribers |

### Generation

**Service:** `App\Services\NewsletterGenerator`  
**Job:** `App\Jobs\GenerateNewsletterJob`

Input: website name, topic, optional angle, full `settings` JSON.  
Output: `{ subject, html, text }` stored in vault with status `ready`.

### Sending

**Job:** `App\Jobs\SendNewsletterJob`  
**Mailable:** `App\Mail\WebsiteNewsletter`

1. Load HTML from vault
2. Query `newsletter_subscribers` where `status = subscribed`
3. Send one email per subscriber
4. Mark newsletter `sent` with recipient count

### Subscribers

**Model:** `NewsletterSubscriber`  
**Table:** `newsletter_subscribers` (unique on `website_id` + `email`)

| Method | Path | Actor | Purpose |
|--------|------|-------|---------|
| GET | `/websites/{website}/subscribers` | Owner | List + manual add |
| POST | `/websites/{website}/subscribers` | Owner | Add subscriber |
| DELETE | `/websites/{website}/subscribers/{subscriber}` | Owner | Remove |
| POST | `/sites/{slug}/newsletter/subscribe` | Public | Subscribe (published sites only) |
| GET | `/sites/{slug}/newsletter/unsubscribe/{token}` | Public | Unsubscribe confirmation page |

Each subscriber receives a unique `unsubscribe_token` on creation.

### Known gaps

- The AI website generator does **not** embed a newsletter signup form on published sites
- Unsubscribe URLs are **not** injected into generated newsletter HTML

---

## Marketing posters

### Storage

Posters live in the per-website vault (`posters` table + `posters/{uuid}/` files).

### Customer routes

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/websites/{website}/posters` | List posters |
| GET | `/websites/{website}/posters/create` | Generation form |
| POST | `/websites/{website}/posters` | Queue generation (spends credits) |
| GET | `/websites/{website}/posters/{uuid}` | Preview HTML |
| GET | `/websites/{website}/posters/{uuid}/download` | Download PNG |

### Formats (`config/sites.poster_formats`)

| Key | Dimensions |
|-----|------------|
| `instagram_square` | 1080 × 1080 |
| `facebook_landscape` | 1200 × 630 |
| `story` | 1080 × 1920 |

### Pipeline

**Services:** `PosterGenerator` (AI HTML) → `PosterExporter` (Browsershot → PNG)  
**Job:** `App\Jobs\GeneratePosterJob`

Poster status:

| Status | Meaning |
|--------|---------|
| `ready` | HTML and PNG exported |
| `html_only` | AI HTML saved; PNG export failed |

---

## Domain management

Full registrar integration via HostAfrica API.

### Routes (prefix `/domains`)

| Area | Routes |
|------|--------|
| List / search | `GET /`, `GET/POST /search`, `POST /suggest` |
| Register | `GET/POST /register` |
| Transfer | `GET/POST /transfer` |
| Domain detail | `GET /{domain}` |
| Renew, sync | `POST /{domain}/renew`, `POST /{domain}/sync` |
| Link to website | `POST /{domain}/link`, `DELETE /{domain}/link` |
| DNS | `GET/POST /{domain}/dns` |
| Nameservers | `GET/POST /{domain}/nameservers` (+ child NS CRUD) |
| Contacts | `GET/POST /{domain}/contacts` |
| Settings | Lock, ID protection, email forwarding, EPP, release, delete |

Linking a domain sets `websites.custom_domain` and syncs the published symlink via `PublishedSiteHost`.

Domain operations spend user credits based on `DomainCreditPricing`.

---

## Background jobs

| Job | Timeout | Retries | Trigger |
|-----|---------|---------|---------|
| `GenerateWebsiteJob` | 900s | 1 | Website create / regenerate |
| `GenerateNewsletterJob` | 300s | 1 | Newsletter create |
| `GeneratePosterJob` | 300s | 1 | Poster create |
| `SendNewsletterJob` | 600s | default | Newsletter send |

All generation jobs require `QUEUE_CONNECTION` (Redis recommended) and a running queue worker.

---

## Per-website content vault

**Class:** `App\Services\WebsiteContentVault`  
**Factory:** `WebsiteContentVault::forWebsite($website)`

### SQLite schema

```sql
product_snapshots (uuid, source, created_at, before_path, after_path, xml_path)
newsletters       (uuid, topic, subject, status, created_at, sent_at, recipient_count, dir_path)
posters           (uuid, format, status, created_at, dir_path, png_path)
```

### Public API (selected)

| Method | Purpose |
|--------|---------|
| `recordProductSnapshot()` | Audit trail for generation / content edits |
| `recordNewsletter()` | Store generated campaign |
| `markNewsletterSent()` | Update status after send |
| `recordPoster()` / `updatePosterPng()` | Store poster artifacts |
| `listNewsletters()` / `listPosters()` / `listProductSnapshots()` | List for UI |
| `findNewsletter()` / `findPoster()` | Single record lookup |
| `newsletterHtml()` / `posterHtml()` / `posterPngPath()` | Read artifact content |
| `counts()` | Aggregate counts for manifest |

Product snapshots record **before/after state** (settings + live offerings from HTML). They are an audit/history mechanism, not a queryable live product catalog.

---

## Admin panel (Filament)

Available to users with `is_admin = true` at `/admin`.

Resources include: Users, Websites, Website Images, Domains, Credit Transactions, Website Subscriptions, Newsletter Subscribers.

This is operator tooling — not part of the customer experience.

---

## Configuration reference

Environment variables (see `.env.example`):

| Variable | Purpose | Default |
|----------|---------|---------|
| `SITES_DOMAIN` | Wildcard subdomain zone | `sites.localhost` |
| `SITES_PUBLISH_PATH` | Live site web root | `storage/app/published` |
| `WEBSITE_DATA_PATH` | Per-website vault root | `storage/app/website-data` |
| `SITES_GENERATION_COST` | Credits per website generation | `1` |
| `NEWSLETTER_GENERATION_COST` | Credits per newsletter | `2` |
| `POSTER_GENERATION_COST` | Credits per poster | `3` |
| `SITES_MAX_IMAGES` | Max photos per website | `10` |
| `EDITING_SUBSCRIPTION_PRICE` | Display price (stub) | `R299/year` |
| `ANTHROPIC_API_KEY` | AI generation | — |

Config file: `config/sites.php`

---

## Gap analysis and roadmap alignment

Planned capability vs current implementation:

| Capability | Status | Current implementation | Recommended direction |
|------------|--------|------------------------|---------------------|
| **Product database per website** | Partial | JSON `settings.offerings` + HTML markers; vault snapshots for history | Add structured `products` table or vault table as source of truth; sync to static HTML via `SiteContentUpdater` |
| **Photos per website** | Implemented | `website_images` + private uploads + site assets | Extend with standalone media library UI; optional public asset CDN path |
| **Videos per website** | Not started | — | New model, storage, upload limits, generation prompt updates |
| **Newsletters per website** | Implemented | Vault storage + generate/send UI | Consider DB index for cross-site reporting; inject unsubscribe links |
| **Newsletter subscribers per website** | Implemented | MySQL table + public subscribe/unsubscribe | Add signup form to generated sites (prompt or post-process injection) |
| **Marketing posters per website** | Implemented | Vault storage + generate/download UI | — |
| **Payments** | Stubbed | Credits and subscriptions added without charge | Stripe / PayFast / Paystack + webhooks |
| **Product snapshot UI** | Partial | Count on website show page only | Browse/restore/compare UI over vault |

### Suggested storage model for future work

```
MySQL (operational)          Vault / files (artifacts)
─────────────────────        ──────────────────────────
websites                     newsletters/{uuid}/
website_images               posters/{uuid}/
products (future)            product_snapshots/ (audit)
newsletter_subscribers
website_subscriptions
```

The per-website vault pattern already isolates marketing artifacts correctly. A future product database should either live in MySQL (queryable, relational) or as first-class vault tables synced to the static site — not remain embedded only in `settings` JSON.

---

## Related code locations

| Area | Path |
|------|------|
| Routes | `routes/web.php` |
| Website model | `app/Models/Website.php` |
| Content vault | `app/Services/WebsiteContentVault.php` |
| Site generator | `app/Services/WebsiteGenerator.php` |
| Content updater | `app/Services/SiteContentUpdater.php` |
| Publish host | `app/Services/PublishedSiteHost.php` |
| Generation prompt | `resources/prompts/website-generator-system.md` |
| Marketing tests | `tests/Feature/MarketingServicesTest.php` |
| Vault tests | `tests/Unit/WebsiteContentVaultTest.php` |
| Site config | `config/sites.php` |

---

*Last updated: July 2026*
