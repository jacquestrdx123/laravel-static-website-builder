<?php

namespace App\Services;

use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Canonical product/service catalog stored in MySQL (websites.product_catalog).
 *
 * @see docs/standards/product-catalog.md
 */
class WebsiteProductCatalog
{
    public const SCHEMA_VERSION = 1;

    public function __construct(private Website $website)
    {
    }

    public static function forWebsite(Website $website): self
    {
        return new self($website);
    }

    /** Load catalog from DB, migrating legacy settings.offerings when empty. */
    public function get(): array
    {
        if (filled($this->website->product_catalog)) {
            return $this->normalize($this->website->product_catalog);
        }

        return $this->normalize($this->buildFromSettings($this->website->settings ?? []));
    }

    /** Persist normalized catalog to MySQL and mirror into settings.offerings for backwards compatibility. */
    public function save(array $catalog): array
    {
        $normalized = $this->normalize($catalog);
        $settings = $this->website->settings ?? [];

        $settings['offering_type'] = $normalized['offering_type'];
        $settings['offering_label'] = $normalized['offering_label'];
        $settings['offerings'] = array_map(fn (array $item) => [
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => $item['price'],
            'image_id' => $this->imageIdForAssetKey($item['image_asset_key'] ?? null),
        ], $normalized['items']);

        $this->website->update([
            'product_catalog' => $normalized,
            'settings' => $settings,
        ]);

        $this->website->refresh();

        return $normalized;
    }

    /**
     * Catalog JSON written beside index.html — the static site's local copy of
     * the MySQL source of truth. SiteContentUpdater overwrites this on every sync.
     *
     * @return array{catalog: array, json: string}
     */
    public function forSiteExport(?WebsiteAssetCdn $cdn = null): array
    {
        $cdn ??= WebsiteAssetCdn::forWebsite($this->website);
        $catalog = $this->withResolvedImageUrls($this->get(), $cdn);
        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return ['catalog' => $catalog, 'json' => $json ?: '{}'];
    }

    public function writeToSite(string $sitePath, ?WebsiteAssetCdn $cdn = null): void
    {
        if (! File::isDirectory($sitePath)) {
            return;
        }

        ['json' => $json] = $this->forSiteExport($cdn);
        File::put($sitePath.'/catalog.json', $json);
    }

    /** @param  list<array{name: string, description?: ?string, price?: ?string, image_id?: ?int}>  $offerings */
    public function buildFromOfferings(
        array $offerings,
        string $offeringType = 'products',
        ?string $offeringLabel = null,
        ?array $existingCatalog = null,
    ): array {
        $existingByName = [];
        if ($existingCatalog !== null) {
            foreach ($existingCatalog['items'] ?? [] as $item) {
                $existingByName[$this->itemKey($item)] = $item;
            }
        }

        $items = [];
        foreach (array_values($offerings) as $sort => $offering) {
            if (! filled($offering['name'] ?? null)) {
                continue;
            }

            $key = $this->itemKey([
                'name' => $offering['name'],
                'price' => $offering['price'] ?? null,
            ]);

            $existing = $existingByName[$key] ?? null;
            $imageId = $offering['image_id'] ?? null;
            $image = $imageId ? $this->website->images()->find($imageId) : null;

            $items[] = [
                'id' => $existing['id'] ?? (string) Str::uuid(),
                'sort' => $sort,
                'name' => $offering['name'],
                'description' => $offering['description'] ?? null,
                'price' => $offering['price'] ?? null,
                'image_asset_key' => $image?->asset_key,
                'active' => true,
            ];
        }

        return $this->normalize([
            'schema_version' => self::SCHEMA_VERSION,
            'offering_type' => $offeringType,
            'offering_label' => $offeringLabel,
            'items' => $items,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function activeItems(): array
    {
        return array_values(array_filter(
            $this->get()['items'],
            fn (array $item) => ($item['active'] ?? true) === true
        ));
    }

    /** @param  array<string, mixed>  $catalog */
    public function normalize(array $catalog): array
    {
        $items = [];
        foreach ($catalog['items'] ?? [] as $index => $item) {
            if (! is_array($item) || ! filled($item['name'] ?? null)) {
                continue;
            }

            $items[] = [
                'id' => (string) ($item['id'] ?? Str::uuid()),
                'sort' => (int) ($item['sort'] ?? $index),
                'name' => (string) $item['name'],
                'description' => filled($item['description'] ?? null) ? (string) $item['description'] : null,
                'price' => filled($item['price'] ?? null) ? (string) $item['price'] : null,
                'image_asset_key' => filled($item['image_asset_key'] ?? null) ? (string) $item['image_asset_key'] : null,
                'active' => ($item['active'] ?? true) !== false,
            ];
        }

        usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'offering_type' => in_array($catalog['offering_type'] ?? 'products', ['products', 'services'], true)
                ? $catalog['offering_type']
                : 'products',
            'offering_label' => filled($catalog['offering_label'] ?? null)
                ? (string) $catalog['offering_label']
                : null,
            'items' => $items,
        ];
    }

    /** @param  array<string, mixed>  $settings */
    private function buildFromSettings(array $settings): array
    {
        return $this->buildFromOfferings(
            $settings['offerings'] ?? [],
            $settings['offering_type'] ?? 'products',
            $settings['offering_label'] ?? null,
        );
    }

    /** @param  array<string, mixed>  $catalog */
    private function withResolvedImageUrls(array $catalog, WebsiteAssetCdn $cdn): array
    {
        $catalog['items'] = array_map(function (array $item) use ($cdn) {
            $item['image_url'] = filled($item['image_asset_key'] ?? null)
                ? $cdn->url($item['image_asset_key'])
                : null;

            return $item;
        }, $catalog['items']);

        return $catalog;
    }

    private function imageIdForAssetKey(?string $assetKey): ?int
    {
        if (blank($assetKey)) {
            return null;
        }

        return $this->website->images()->where('asset_key', $assetKey)->value('id');
    }

    /** @param  array<string, mixed>  $item */
    private function itemKey(array $item): string
    {
        return strtolower(trim($item['name'] ?? '')).'|'.trim((string) ($item['price'] ?? ''));
    }
}
