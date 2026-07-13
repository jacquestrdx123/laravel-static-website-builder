<?php

namespace App\Services;

use App\Models\Website;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\File;

/**
 * Applies structured-content edits to an already-generated static site by
 * rewriting annotated HTML from the MySQL product catalog — no AI call.
 *
 * @see docs/standards/product-catalog.md
 */
class SiteContentUpdater
{
    /** @return int number of HTML files that were rewritten */
    public function apply(Website $website): int
    {
        $sitePath = $website->sitePath();

        if (! File::isDirectory($sitePath)) {
            return 0;
        }

        $website->loadMissing('images');
        $catalog = WebsiteProductCatalog::forWebsite($website);
        $cdn = WebsiteAssetCdn::forWebsite($website);
        ['catalog' => $export] = $catalog->forSiteExport($cdn);
        $items = array_values(array_filter(
            $export['items'],
            fn (array $item) => ($item['active'] ?? true) === true
        ));

        $changed = 0;

        foreach (File::allFiles($sitePath) as $file) {
            if (! in_array(strtolower($file->getExtension()), ['html', 'htm'], true)) {
                continue;
            }

            if ($this->updateHtmlFile($file->getPathname(), $website->settings, $items)) {
                $changed++;
            }
        }

        $catalog->writeToSite($sitePath, $cdn);

        $published = config('sites.publish_path').'/'.$website->slug;
        if ($changed > 0 && File::isDirectory($published)) {
            File::deleteDirectory($published);
            File::copyDirectory($sitePath, $published);
        }

        return $changed;
    }

    public function supportsEditing(Website $website): bool
    {
        $index = $website->sitePath().'/index.html';

        if (! File::exists($index)) {
            return false;
        }

        $html = File::get($index);

        return str_contains($html, 'data-catalog-item')
            || str_contains($html, 'data-offering')
            || str_contains($html, 'data-content');
    }

    /**
     * Read live offering copy from generated HTML (for form prefill).
     *
     * @return list<array{id: ?string, name: string, description: ?string, price: ?string, image_asset_key: ?string}>
     */
    public function readOfferingsFromSite(Website $website): array
    {
        $website->loadMissing('images');

        $index = $website->sitePath().'/index.html';

        if (! File::exists($index)) {
            return [];
        }

        $html = File::get($index);

        if (! str_contains($html, 'data-catalog-item') && ! str_contains($html, 'data-offering')) {
            return [];
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        $doc->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $cdn = WebsiteAssetCdn::forWebsite($website);
        $assetKeyByUrl = array_flip($cdn->urlMap());
        $imageIdBySiteAssetPath = $this->imageIdBySiteAssetPath($website);
        $offerings = [];

        $nodes = $xpath->query('//*[@data-catalog-item]');
        if ($nodes->length === 0) {
            $nodes = $xpath->query('//*[@data-offering]');
        }

        foreach ($nodes as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $imageAssetKey = null;
            $imageId = null;

            foreach ($xpath->query('.//*[@data-field="image"]', $item) as $element) {
                if (! $element instanceof DOMElement || strtolower($element->tagName) !== 'img') {
                    continue;
                }

                $src = trim($element->getAttribute('src'));
                if ($src === '') {
                    continue;
                }

                if (isset($assetKeyByUrl[$src])) {
                    $imageAssetKey = $assetKeyByUrl[$src];
                    break;
                }

                $path = ltrim(str_replace('\\', '/', parse_url($src, PHP_URL_PATH) ?? $src), '/');
                $path = preg_replace('#^(\.\./)+#', '', $path) ?? $path;

                if (isset($assetKeyByUrl[$path])) {
                    $imageAssetKey = $assetKeyByUrl[$path];
                    break;
                }

                if (preg_match('#/cdn/\d+/([0-9a-f-]{36})$#i', $path, $matches)) {
                    $imageAssetKey = $matches[1];
                    break;
                }

                if (isset($imageIdBySiteAssetPath[$path])) {
                    $imageId = $imageIdBySiteAssetPath[$path];
                    $image = $website->images->firstWhere('id', $imageId);
                    $imageAssetKey = $image?->asset_key;
                    break;
                }
            }

            $offerings[] = [
                'id' => $item->getAttribute('data-catalog-item') ?: null,
                'name' => $this->fieldText($xpath, $item, 'name'),
                'description' => $this->fieldText($xpath, $item, 'description') ?: null,
                'price' => $this->fieldText($xpath, $item, 'price') ?: null,
                'image_asset_key' => $imageAssetKey,
                'image_id' => $imageId,
            ];
        }

        return $this->dedupeOfferings($offerings);
    }

    /** @param  list<array<string, mixed>>  $items  Catalog items with resolved image_url */
    private function updateHtmlFile(string $path, array $settings, array $items): bool
    {
        $html = File::get($path);

        if (! str_contains($html, 'data-catalog-item')
            && ! str_contains($html, 'data-offering')
            && ! str_contains($html, 'data-content')) {
            return false;
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        $doc->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        $dirty = $this->syncCatalogItems($xpath, $items);
        $dirty = $this->updateSimpleFields($xpath, $settings) || $dirty;

        if (! $dirty) {
            return false;
        }

        File::put($path, $doc->saveHTML());

        return true;
    }

    /** @param  list<array<string, mixed>>  $items */
    private function syncCatalogItems(DOMXPath $xpath, array $items): bool
    {
        if ($items === []) {
            return false;
        }

        $nodes = iterator_to_array($xpath->query('//*[@data-catalog-item]'));
        $legacy = $nodes === [] ? iterator_to_array($xpath->query('//*[@data-offering]')) : [];

        if ($nodes === [] && $legacy === []) {
            return false;
        }

        $byId = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement && filled($node->getAttribute('data-catalog-item'))) {
                $byId[$node->getAttribute('data-catalog-item')] = $node;
            }
        }

        $groups = $this->offeringGroups($nodes !== [] ? $nodes : $legacy);
        $dirty = false;

        foreach ($groups as $group) {
            $parent = $group['parent'];
            $template = $group['items'][0];
            $anchor = $template;

            foreach ($items as $item) {
                $existing = $byId[$item['id']] ?? null;

                if ($existing instanceof DOMElement && $existing->parentNode === $parent) {
                    $this->applyItemFields($xpath, $existing, $item);
                    $dirty = true;

                    continue;
                }

                /** @var DOMElement $clone */
                $clone = $template->cloneNode(true);
                $clone->setAttribute('data-catalog-item', (string) $item['id']);
                $clone->removeAttribute('data-offering');
                $this->applyItemFields($xpath, $clone, $item);
                $parent->insertBefore($clone, $anchor);
                $dirty = true;
            }

            foreach ($group['items'] as $old) {
                if (! $old instanceof DOMElement) {
                    continue;
                }

                $id = $old->getAttribute('data-catalog-item');
                $stillActive = $id !== '' && collect($items)->contains(fn ($item) => $item['id'] === $id);

                if (! $stillActive) {
                    $parent->removeChild($old);
                    $dirty = true;
                }
            }
        }

        return $dirty;
    }

    /** @param  list<\DOMNode>  $items */
    private function offeringGroups(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $groups[spl_object_id($item->parentNode)]['parent'] = $item->parentNode;
            $groups[spl_object_id($item->parentNode)]['items'][] = $item;
        }

        return array_values($groups);
    }

    /** @param  array<string, mixed>  $item */
    private function applyItemFields(DOMXPath $xpath, DOMElement $element, array $item): void
    {
        $element->setAttribute('data-catalog-item', (string) $item['id']);
        $this->setField($xpath, $element, 'name', $item['name'] ?? '');
        $this->setField($xpath, $element, 'description', $item['description'] ?? '');
        $this->setField($xpath, $element, 'price', $item['price'] ?? '');
        $this->setImageField($xpath, $element, $item['image_url'] ?? null);
    }

    private function setImageField(DOMXPath $xpath, DOMElement $item, ?string $imageUrl): void
    {
        if (blank($imageUrl)) {
            return;
        }

        foreach ($xpath->query('.//*[@data-field="image"]', $item) as $element) {
            if (strtolower($element->tagName) === 'img') {
                $element->setAttribute('src', $imageUrl);
            }
        }
    }

    private function fieldText(DOMXPath $xpath, DOMElement $item, string $field): string
    {
        foreach ($xpath->query('.//*[@data-field="'.$field.'"]', $item) as $element) {
            return trim($element->textContent ?? '');
        }

        if ($item->getAttribute('data-field') === $field) {
            return trim($item->textContent ?? '');
        }

        return '';
    }

    private function setField(DOMXPath $xpath, DOMElement $item, string $field, ?string $value): void
    {
        foreach ($xpath->query('.//*[@data-field="'.$field.'"]', $item) as $element) {
            $element->textContent = $value ?? '';
        }

        if ($item->getAttribute('data-field') === $field) {
            $item->textContent = $value ?? '';
        }
    }

    private function updateSimpleFields(DOMXPath $xpath, array $settings): bool
    {
        $dirty = false;

        $fields = [
            'tagline' => $settings['tagline'] ?? null,
            'contact-email' => $settings['contact_email'] ?? null,
        ];

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }

            foreach ($xpath->query('//*[@data-content="'.$key.'"]') as $element) {
                /** @var DOMElement $element */
                $element->textContent = $value;

                if ($key === 'contact-email'
                    && strtolower($element->tagName) === 'a'
                    && str_starts_with($element->getAttribute('href'), 'mailto:')) {
                    $element->setAttribute('href', 'mailto:'.$value);
                }

                $dirty = true;
            }
        }

        return $dirty;
    }

    /** @param  list<array<string, mixed>>  $offerings */
    private function dedupeOfferings(array $offerings): array
    {
        $seen = [];
        $unique = [];

        foreach ($offerings as $offering) {
            $key = ($offering['name'] ?? '').'|'.($offering['price'] ?? '');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $offering;
        }

        return $unique;
    }

    /** @return array<string, int> site-relative asset path => website_images.id */
    private function imageIdBySiteAssetPath(Website $website): array
    {
        $map = [];

        foreach ($website->images as $image) {
            $map['assets/'.$image->assetName()] = $image->id;
        }

        return $map;
    }
}
