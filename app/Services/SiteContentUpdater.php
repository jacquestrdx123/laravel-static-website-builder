<?php

namespace App\Services;

use App\Models\Website;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\File;

/**
 * Applies structured-content edits (offerings, tagline, contact email) to an
 * already-generated static site by rewriting the elements the generation spec
 * requires to be annotated with data-offering / data-field / data-content.
 * Deterministic and free: no AI call involved.
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

        $changed = 0;

        $imageAssets = $this->imageAssetsById($website);

        foreach (File::allFiles($sitePath) as $file) {
            if (! in_array(strtolower($file->getExtension()), ['html', 'htm'], true)) {
                continue;
            }

            if ($this->updateHtmlFile($file->getPathname(), $website->settings, $imageAssets)) {
                $changed++;
            }
        }

        // Keep the live copy in sync when the site is published.
        $published = config('sites.publish_path').'/'.$website->slug;
        if ($changed > 0 && File::isDirectory($published)) {
            File::deleteDirectory($published);
            File::copyDirectory($sitePath, $published);
        }

        return $changed;
    }

    /** Whether a generated site carries the markers this updater needs. */
    public function supportsEditing(Website $website): bool
    {
        $index = $website->sitePath().'/index.html';

        return File::exists($index)
            && (str_contains(File::get($index), 'data-offering')
                || str_contains(File::get($index), 'data-content'));
    }

    /**
     * Read the live offering copy from the generated site. When the AI elaborated
     * descriptions during generation, that text lives in the HTML — not in settings.
     *
     * @return list<array{name: string, description: ?string, price: ?string, image_id: ?int}>
     */
    public function readOfferingsFromSite(Website $website): array
    {
        $index = $website->sitePath().'/index.html';

        if (! File::exists($index) || ! str_contains(File::get($index), 'data-offering')) {
            return [];
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        $doc->loadHTML(mb_encode_numericentity(File::get($index), [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $assetToImageId = array_flip($this->imageAssetsById($website));
        $offerings = [];

        foreach ($xpath->query('//*[@data-offering]') as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $imageId = null;

            foreach ($xpath->query('.//*[@data-field="image"]', $item) as $element) {
                if (! $element instanceof DOMElement || strtolower($element->tagName) !== 'img') {
                    continue;
                }

                $src = $element->getAttribute('src');

                if ($src !== '' && isset($assetToImageId[$src])) {
                    $imageId = $assetToImageId[$src];
                    break;
                }
            }

            $offerings[] = [
                'name' => $this->fieldText($xpath, $item, 'name'),
                'description' => $this->fieldText($xpath, $item, 'description') ?: null,
                'price' => $this->fieldText($xpath, $item, 'price') ?: null,
                'image_id' => $imageId,
            ];
        }

        // The first offerings group is authoritative; additional groups are duplicates.
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

    private function updateHtmlFile(string $path, array $settings, array $imageAssets): bool
    {
        $html = File::get($path);

        if (! str_contains($html, 'data-offering') && ! str_contains($html, 'data-content')) {
            return false;
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        // Entity-encode non-ASCII so loadHTML doesn't mangle UTF-8.
        $doc->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        $dirty = $this->rebuildOfferings($xpath, $settings['offerings'] ?? [], $imageAssets);
        $dirty = $this->updateSimpleFields($xpath, $settings) || $dirty;

        if (! $dirty) {
            return false;
        }

        File::put($path, $doc->saveHTML());

        return true;
    }

    /**
     * Rebuild every annotated offerings section: the first existing item is
     * used as the template, cloned once per offering, and the old items are
     * removed. An empty offerings list leaves the section untouched (a blank
     * section would look broken).
     */
    private function rebuildOfferings(DOMXPath $xpath, array $offerings, array $imageAssets): bool
    {
        if ($offerings === []) {
            return false;
        }

        $items = iterator_to_array($xpath->query('//*[@data-offering]'));

        if ($items === []) {
            return false;
        }

        // Items may appear in more than one section (e.g. menu + pricing);
        // rebuild each parent group independently.
        $groups = [];
        foreach ($items as $item) {
            $groups[spl_object_id($item->parentNode)]['parent'] = $item->parentNode;
            $groups[spl_object_id($item->parentNode)]['items'][] = $item;
        }

        foreach ($groups as $group) {
            $parent = $group['parent'];
            $template = $group['items'][0];
            $anchor = $template;

            foreach ($offerings as $index => $offering) {
                /** @var DOMElement $clone */
                $clone = $template->cloneNode(true);
                $clone->setAttribute('data-offering', (string) ($index + 1));
                $this->setField($xpath, $clone, 'name', $offering['name'] ?? '');
                $this->setField($xpath, $clone, 'description', $offering['description'] ?? '');
                $this->setField($xpath, $clone, 'price', $offering['price'] ?? '');
                $this->setImageField($xpath, $clone, $offering['image_id'] ?? null, $imageAssets);
                $parent->insertBefore($clone, $anchor);
            }

            foreach ($group['items'] as $old) {
                $parent->removeChild($old);
            }
        }

        return true;
    }

    private function imageAssetsById(Website $website): array
    {
        $assets = [];

        foreach ($website->images as $image) {
            $assets[$image->id] = 'assets/'.$image->assetName();
        }

        return $assets;
    }

    private function setImageField(DOMXPath $xpath, DOMElement $item, mixed $imageId, array $imageAssets): void
    {
        if ($imageId === null || $imageId === '') {
            return;
        }

        $src = $imageAssets[(int) $imageId] ?? null;

        if ($src === null) {
            return;
        }

        foreach ($xpath->query('.//*[@data-field="image"]', $item) as $element) {
            if (strtolower($element->tagName) === 'img') {
                $element->setAttribute('src', $src);
            }
        }
    }

    private function setField(DOMXPath $xpath, DOMElement $item, string $field, ?string $value): void
    {
        foreach ($xpath->query('.//*[@data-field="'.$field.'"]', $item) as $element) {
            $element->textContent = $value ?? '';
        }

        // The item element itself may carry the field attribute.
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
                continue; // never blank out content the customer didn't provide
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
}
