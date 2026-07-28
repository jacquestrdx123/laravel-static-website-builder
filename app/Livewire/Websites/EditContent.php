<?php

namespace App\Livewire\Websites;

use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\SiteContentUpdater;
use App\Services\WebsiteAssetCdn;
use App\Services\WebsiteContentVault;
use App\Services\WebsiteProductCatalog;
use App\WebsiteBuilder\WebsiteOptions;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditContent extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $websiteId;

    public string $tagline = '';

    public string $contact_email = '';

    public string $offering_type = 'services';

    public string $offering_label = '';

    /** @var list<array{name: string, description: string, price: string, image_id: int|null, image: mixed}> */
    public array $offerings = [];

    public bool $editable = false;

    public function mount(Website $website, SiteContentUpdater $updater): void
    {
        abort_unless($website->user_id === auth()->id(), 403);
        abort_unless($website->isGenerated(), 404);

        $website->load('images');
        $cdn = WebsiteAssetCdn::forWebsite($website);
        foreach ($website->images as $image) {
            if ($image->existsOnDisk()) {
                try {
                    $cdn->publish($image);
                } catch (\Throwable) {
                    // Preview still works via authenticated image route.
                }
            }
        }
        $website->load('images');

        $this->websiteId = $website->id;
        $this->tagline = (string) ($website->settings['tagline'] ?? '');
        $this->contact_email = (string) ($website->settings['contact_email'] ?? '');
        $this->offering_type = (string) ($website->settings['offering_type'] ?? 'services');
        $this->offering_label = (string) ($website->settings['offering_label'] ?? '');
        $this->editable = $updater->supportsEditing($website);
        $this->offerings = $this->offeringsForForm($website, $updater);
    }

    public function addOffering(): void
    {
        if (count($this->offerings) >= WebsiteOptions::MAX_OFFERINGS) {
            return;
        }

        $this->offerings[] = [
            'name' => '',
            'description' => '',
            'price' => '',
            'image_id' => null,
            'image' => null,
        ];
    }

    public function removeOffering(int $index): void
    {
        if (count($this->offerings) === 1) {
            $this->offerings[0] = [
                'name' => '',
                'description' => '',
                'price' => '',
                'image_id' => null,
                'image' => null,
            ];

            return;
        }

        unset($this->offerings[$index]);
        $this->offerings = array_values($this->offerings);
    }

    public function save(SiteContentUpdater $updater): mixed
    {
        $website = $this->website()->load('images');

        $validated = $this->validate([
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'offering_type' => ['required', 'in:'.implode(',', WebsiteOptions::OFFERING_TYPES)],
            'offering_label' => ['nullable', 'string', 'max:50'],
            'offerings' => ['nullable', 'array', 'max:'.WebsiteOptions::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
            'offerings.*.image_id' => ['nullable', 'integer'],
            'offerings.*.image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
        ]);

        $settingsBefore = [
            'product_catalog' => WebsiteProductCatalog::forWebsite($website)->get(),
            'settings' => $website->settings,
            'offerings_live' => $updater->readOfferingsFromSite($website),
        ];

        $offerings = array_values(array_filter(
            $validated['offerings'] ?? [],
            fn ($offering) => filled($offering['name'] ?? null)
        ));

        $validImageIds = $website->images()->pluck('id')->all();
        $nextSort = (int) $website->images()->max('sort') + 1;
        $cdn = WebsiteAssetCdn::forWebsite($website);
        $existingCatalog = WebsiteProductCatalog::forWebsite($website)->get();

        $normalizedOfferings = [];
        foreach ($offerings as $index => $offering) {
            $imageId = filled($offering['image_id'] ?? null) ? (int) $offering['image_id'] : null;

            if (($offering['image'] ?? null) !== null) {
                if ($website->images()->count() >= config('sites.max_images')) {
                    $this->addError('offerings.'.$index.'.image', 'This website already has the maximum number of photos.');

                    return null;
                }

                $upload = $offering['image'];
                $path = $upload->store('uploads/'.$website->id, 'local');

                $image = $website->images()->create([
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'type' => WebsiteImage::TYPE_PRODUCT,
                    'mime_type' => $upload->getMimeType(),
                    'sort' => $nextSort,
                ]);

                $cdn->publish($image);

                $nextSort++;
                $validImageIds[] = $image->id;
                $imageId = $image->id;
            }

            if ($imageId !== null && ! in_array($imageId, $validImageIds, true)) {
                $imageId = null;
            }

            $normalizedOfferings[] = [
                'name' => $offering['name'],
                'description' => $offering['description'] ?? null,
                'price' => $offering['price'] ?? null,
                'image_id' => $imageId,
            ];
        }

        $catalog = WebsiteProductCatalog::forWebsite($website)->buildFromOfferings(
            $normalizedOfferings,
            $validated['offering_type'],
            filled($validated['offering_label'] ?? null) ? $validated['offering_label'] : null,
            $existingCatalog,
        );

        WebsiteProductCatalog::forWebsite($website)->save($catalog);

        $website->refresh();

        $website->update([
            'settings' => array_merge($website->settings, [
                'tagline' => $validated['tagline'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
            ]),
        ]);

        $changed = $updater->apply($website->fresh(['images']));

        if ($changed === 0) {
            session()->flash('error', 'Saved, but this site was generated before content editing existed - regenerate it once to enable live updates.');

            return $this->redirect(route('websites.content.edit', $website), navigate: true);
        }

        try {
            WebsiteContentVault::forWebsite($website)->recordProductSnapshot('content_edit', $settingsBefore, [
                'product_catalog' => WebsiteProductCatalog::forWebsite($website)->get(),
                'settings' => $website->fresh()->settings,
                'offerings_live' => $updater->readOfferingsFromSite($website),
            ]);
        } catch (\Throwable) {
            // Non-fatal: content was still updated on the live site.
        }

        session()->flash('status', 'Content updated on your site.');

        return $this->redirect(route('websites.show', $website), navigate: true);
    }

    public function render()
    {
        $website = $this->website()->load('images');

        return view('livewire.websites.edit-content', [
            'website' => $website,
            'images' => $website->images,
            'imagesById' => $website->images->keyBy('id'),
            'offeringTypes' => WebsiteOptions::OFFERING_TYPES,
            'maxOfferings' => WebsiteOptions::MAX_OFFERINGS,
        ])->extends('layouts.app')->title('Edit content — '.$website->name);
    }

    private function website(): Website
    {
        return Website::query()->findOrFail($this->websiteId);
    }

    /** @return list<array{name: string, description: string, price: string, image_id: int|null, image: null}> */
    private function offeringsForForm(Website $website, SiteContentUpdater $updater): array
    {
        $catalog = WebsiteProductCatalog::forWebsite($website)->get();
        $imagesByKey = $website->images->keyBy('asset_key');

        if ($catalog['items'] !== []) {
            $live = $updater->readOfferingsFromSite($website);
            $liveById = collect($live)->keyBy('id');
            $liveByKey = collect($live)->keyBy(fn ($row) => strtolower($row['name']).'|'.($row['price'] ?? ''));
            $storedByKey = collect($website->settings['offerings'] ?? [])->keyBy(
                fn ($row) => strtolower($row['name'] ?? '').'|'.($row['price'] ?? '')
            );

            return array_map(function (array $item) use ($website, $imagesByKey, $liveById, $liveByKey, $storedByKey) {
                $liveOffering = $liveById->get($item['id'])
                    ?? $liveByKey->get(strtolower($item['name']).'|'.($item['price'] ?? ''));
                $storedOffering = $storedByKey->get(strtolower($item['name']).'|'.($item['price'] ?? ''));

                $imageId = $this->resolveOfferingImageId(
                    $website,
                    $imagesByKey,
                    $item,
                    is_array($liveOffering) ? $liveOffering : null,
                    is_array($storedOffering) ? $storedOffering : null,
                );

                return [
                    'name' => $item['name'],
                    'description' => (is_array($liveOffering) ? $liveOffering['description'] : null)
                        ?? $item['description']
                        ?? '',
                    'price' => $item['price'] ?? '',
                    'image_id' => $imageId,
                    'image' => null,
                ];
            }, $catalog['items']);
        }

        $stored = $website->settings['offerings'] ?? [];
        $live = $updater->readOfferingsFromSite($website);

        if ($stored === [] && $live === []) {
            return [['name' => '', 'description' => '', 'price' => '', 'image_id' => null, 'image' => null]];
        }

        $count = max(count($stored), count($live));
        $offerings = [];

        for ($index = 0; $index < $count; $index++) {
            $storedOffering = $stored[$index] ?? [];
            $liveOffering = $live[$index] ?? [];

            $imageId = $storedOffering['image_id'] ?? null;
            if ($imageId === null && filled($liveOffering['image_asset_key'] ?? null)) {
                $imageId = $website->images->firstWhere('asset_key', $liveOffering['image_asset_key'])?->id;
            }
            if ($imageId === null && filled($liveOffering['image_id'] ?? null)) {
                $imageId = (int) $liveOffering['image_id'];
            }

            $offerings[] = [
                'name' => $liveOffering['name'] ?? $storedOffering['name'] ?? '',
                'description' => $liveOffering['description'] ?? $storedOffering['description'] ?? '',
                'price' => $liveOffering['price'] ?? $storedOffering['price'] ?? '',
                'image_id' => $imageId,
                'image' => null,
            ];
        }

        return $offerings;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, WebsiteImage>  $imagesByKey
     * @param  array<string, mixed>  $catalogItem
     * @param  array<string, mixed>|null  $liveOffering
     * @param  array<string, mixed>|null  $storedOffering
     */
    private function resolveOfferingImageId(
        Website $website,
        $imagesByKey,
        array $catalogItem,
        ?array $liveOffering,
        ?array $storedOffering,
    ): ?int {
        foreach ([
            $catalogItem['image_asset_key'] ?? null,
            $liveOffering['image_asset_key'] ?? null,
        ] as $assetKey) {
            if (filled($assetKey) && $imagesByKey->has($assetKey)) {
                return $imagesByKey->get($assetKey)->id;
            }
        }

        foreach ([
            $storedOffering['image_id'] ?? null,
            $liveOffering['image_id'] ?? null,
        ] as $imageId) {
            if (filled($imageId) && $website->images->contains('id', (int) $imageId)) {
                return (int) $imageId;
            }
        }

        return null;
    }
}
