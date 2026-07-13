# Product catalog standard (v1)

**Status:** Active  
**Related:** [Customer platform architecture](../architecture/customer-platform.md)

This document defines how products, services, and menu items are stored, synced to static sites, and kept editable **without AI regeneration**.

---

## Design goals

1. **MySQL is the source of truth** — the catalog lives in `websites.product_catalog` (JSON).
2. **Stable identity** — each item has a permanent UUID (`id`) that survives reorders and renames of display fields.
3. **CDN-backed product images** — product photos use immutable URLs so HTML can be updated without copying files into the site bundle.
4. **Deterministic HTML sync** — `SiteContentUpdater` rewrites annotated HTML from the catalog; no API calls on the live site.
5. **AI-safe generation** — the generator prompt requires exact catalog fidelity; the platform overwrites `catalog.json` after generation.

---

## MySQL storage

### Column

`websites.product_catalog` — nullable JSON, cast to array on the `Website` model.

Legacy sites without this column populated fall back to `settings.offerings` on read via `WebsiteProductCatalog::get()`.

### JSON schema (version 1)

```json
{
  "schema_version": 1,
  "offering_type": "products",
  "offering_label": "Our Products",
  "items": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "sort": 0,
      "name": "Sourdough loaf",
      "description": "Naturally leavened, baked daily",
      "price": "R65",
      "image_asset_key": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
      "active": true
    }
  ]
}
```

| Field | Type | Rules |
|-------|------|-------|
| `schema_version` | integer | Must be `1` |
| `offering_type` | string | `products` or `services` |
| `offering_label` | string\|null | Section heading override |
| `items[].id` | UUID string | Assigned on create; **never change** |
| `items[].sort` | integer | Display order (0-based) |
| `items[].name` | string | Required |
| `items[].description` | string\|null | Optional |
| `items[].price` | string\|null | Verbatim customer text (e.g. `from R2,500`) |
| `items[].image_asset_key` | UUID\|null | References `website_images.asset_key` |
| `items[].active` | boolean | Default `true`; inactive items are omitted from sync |

**Not stored in MySQL:** `image_url` — resolved at export time from the asset CDN.

### Backwards compatibility

`WebsiteProductCatalog::save()` mirrors the catalog into `settings.offerings` (with `image_id`) so older code paths keep working during migration.

---

## Asset CDN

Product (and all upload) images get a stable **`asset_key`** (UUID) on `website_images`.

### Storage layout

```
storage/app/public/cdn/{website_id}/{asset_key}.{ext}
```

### Public URL

```
{CDN_BASE_URL}/cdn/{website_id}/{asset_key}
```

Default `CDN_BASE_URL` = `APP_URL`. Customer sites on `{slug}.sites.example.com` load product images from the builder origin cross-origin.

### Configuration

| Env / config | Purpose |
|--------------|---------|
| `CDN_BASE_URL` | Origin for absolute image URLs |
| `CDN_DISK` | Filesystem disk (default `public`) |
| `CDN_CACHE_MAX_AGE` | `Cache-Control` max-age (default 1 year, immutable) |

### Route

`GET /cdn/{website}/{assetKey}` → `AssetCdnController@show`

On first request, publishes from private upload storage if the CDN copy is missing.

---

## Static site copies

Each generated site includes:

| File | Source | Purpose |
|------|--------|---------|
| `catalog.json` | MySQL catalog + resolved `image_url` | Local mirror / debugging; overwritten on every sync |
| `index.html` (etc.) | AI + `SiteContentUpdater` | Human-visible rendering |

**Important:** `catalog.json` on disk is **not** the source of truth. MySQL is. The updater always writes the latest export after edits.

---

## HTML rendering contract

Every catalog item rendered on the site must follow this DOM structure:

```html
<article data-catalog-item="550e8400-e29b-41d4-a716-446655440000">
  <h3 data-field="name">Sourdough loaf</h3>
  <p data-field="description">Naturally leavened, baked daily</p>
  <span data-field="price">R65</span>
  <img data-field="image"
       src="https://app.example.com/cdn/12/7c9e6679-7425-40de-944b-e07fc1f90ae7"
       alt="Sourdough loaf" />
</article>
```

### Rules

| Rule | Detail |
|------|--------|
| Root attribute | `data-catalog-item="{id}"` must match MySQL item `id` exactly |
| Text fields | `data-field="name"`, `"description"`, `"price"` — always present (hide empty with CSS `:empty`) |
| Product images | `data-field="image"` on `<img>`; `src` = CDN `image_url` when set |
| Sibling structure | All items in a section share the same parent and inner DOM template |
| Legacy | `data-offering="N"` still supported for old sites; new generations must use `data-catalog-item` |

### Non-catalog content

| Attribute | Field |
|-----------|-------|
| `data-content="tagline"` | Tagline text |
| `data-content="contact-email"` | Email (on `<a href="mailto:...">` when linked) |

---

## Sync pipeline (no AI)

```
Customer saves content edit form
  → WebsiteProductCatalog::save()        (MySQL)
  → SiteContentUpdater::apply()
       → For each HTML file:
            Match nodes by data-catalog-item id
            Rewrite name / description / price / image src
            Clone template for new items; remove inactive ids
       → Write catalog.json
       → Re-copy to published path if live
```

**Service classes:**

- `App\Services\WebsiteProductCatalog` — CRUD + export
- `App\Services\WebsiteAssetCdn` — publish + URL resolution
- `App\Services\SiteContentUpdater` — HTML rewrite

---

## AI generation contract

The brief sent to Claude includes `product_catalog` with resolved `image_url` values.

The model **must**:

1. Emit `catalog.json` matching the brief (platform overwrites it anyway as a safety net)
2. Render every active catalog item with matching `data-catalog-item` ids
3. Use exact `image_url` for product `<img>` tags
4. Never rename, re-price, or drop catalog items

See `resources/prompts/website-generator-system.md` → `<product_catalog_contract>`.

After generation, `WebsiteGenerator` **always** writes `catalog.json` from MySQL so AI drift cannot corrupt the canonical file.

---

## Editing workflow (customer)

1. Purchase manual editing subscription (stub today)
2. Open **Edit content** on a generated site
3. Change offerings — IDs preserved when names/prices match existing items
4. Upload new product photo → published to CDN → linked via `image_asset_key`
5. Save → static HTML + `catalog.json` updated; published site refreshed

No credits consumed. No queue job. No AI call.

---

## Vault snapshots

Product change history in the content vault now records `product_catalog` before/after alongside live HTML readings.

---

## Future extensions (not implemented)

| Extension | Notes |
|-----------|-------|
| Schema v2 | Add variants, SKUs, stock — bump `schema_version` |
| Video assets | New `website_media` type + CDN MIME handling |
| `catalog.json` client hydrate | Optional JS for SPAs — not required for static sites |
| Admin catalog UI | Dedicated product manager separate from content form |

---

## Code index

| Component | Path |
|-----------|------|
| Catalog service | `app/Services/WebsiteProductCatalog.php` |
| CDN service | `app/Services/WebsiteAssetCdn.php` |
| CDN controller | `app/Http/Controllers/AssetCdnController.php` |
| HTML sync | `app/Services/SiteContentUpdater.php` |
| Migration | `database/migrations/2026_07_13_120001_add_product_catalog_to_websites_table.php` |
| Asset key migration | `database/migrations/2026_07_13_120002_add_asset_key_to_website_images_table.php` |

---

*Last updated: July 2026*
