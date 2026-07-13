<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\SiteContentUpdater;
use App\Services\WebsiteAssetCdn;
use App\Services\WebsiteContentVault;
use App\Services\WebsiteProductCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edits to the business data of an already-generated site (requires active
 * manual-editing subscription): offerings, tagline, and contact email.
 */
class ContentController extends Controller
{
    public function edit(Request $request, Website $website, SiteContentUpdater $updater): View|RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        if (! $website->hasActiveEditingSubscription()) {
            return redirect()->route('websites.subscription.show', $website)
                ->with('error', 'Manual content editing requires an active yearly subscription.');
        }

        $website->load('images');

        return view('websites.content', [
            'website' => $website,
            'editable' => $updater->supportsEditing($website),
            'images' => $website->images,
            'imagesById' => $website->images->keyBy('id'),
            'offerings' => $this->offeringsForForm($website, $updater),
            'catalog' => WebsiteProductCatalog::forWebsite($website)->get(),
        ]);
    }

    public function image(Request $request, Website $website, WebsiteImage $image): Response
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($image->website_id === $website->id, 404);
        abort_unless($image->existsOnDisk(), 404);

        return response()->file(Storage::disk('local')->path($image->path), [
            'Content-Type' => $image->mime_type,
        ]);
    }

    public function update(Request $request, Website $website, SiteContentUpdater $updater): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        if (! $website->hasActiveEditingSubscription()) {
            return redirect()->route('websites.subscription.show', $website)
                ->with('error', 'Manual content editing requires an active yearly subscription.');
        }

        $settingsBefore = [
            'product_catalog' => WebsiteProductCatalog::forWebsite($website)->get(),
            'settings' => $website->settings,
            'offerings_live' => $updater->readOfferingsFromSite($website),
        ];

        $data = $request->validate([
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'offering_type' => ['required', 'in:'.implode(',', WebsiteController::OFFERING_TYPES)],
            'offering_label' => ['nullable', 'string', 'max:50'],
            'offerings' => ['nullable', 'array', 'max:'.WebsiteController::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
            'offerings.*.image_id' => ['nullable', 'integer'],
            'offerings.*.image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
        ]);

        $offerings = array_values(array_filter(
            $data['offerings'] ?? [],
            fn ($offering) => filled($offering['name'] ?? null)
        ));

        $validImageIds = $website->images()->pluck('id')->all();
        $nextSort = (int) $website->images()->max('sort') + 1;
        $cdn = WebsiteAssetCdn::forWebsite($website);
        $existingCatalog = WebsiteProductCatalog::forWebsite($website)->get();

        $normalizedOfferings = [];
        foreach ($offerings as $index => $offering) {
            $imageId = filled($offering['image_id'] ?? null) ? (int) $offering['image_id'] : null;

            if ($request->hasFile("offerings.$index.image")) {
                if ($website->images()->count() >= config('sites.max_images')) {
                    return redirect()->route('websites.content.edit', $website)
                        ->withErrors(['offerings.'.$index.'.image' => 'This website already has the maximum number of photos.'])
                        ->withInput();
                }

                $upload = $request->file("offerings.$index.image");
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
            $data['offering_type'],
            filled($data['offering_label'] ?? null) ? $data['offering_label'] : null,
            $existingCatalog,
        );

        WebsiteProductCatalog::forWebsite($website)->save($catalog);

        $website->refresh();

        $website->update([
            'settings' => array_merge($website->settings, [
                'tagline' => $data['tagline'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
            ]),
        ]);

        $changed = $updater->apply($website->fresh(['images']));

        if ($changed === 0) {
            return redirect()->route('websites.content.edit', $website)
                ->with('error', 'Saved, but this site was generated before content editing existed - regenerate it once to enable live updates.');
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

        return redirect()->route('websites.show', $website)
            ->with('status', 'Content updated on your site.');
    }

    private function offeringsForForm(Website $website, SiteContentUpdater $updater): array
    {
        if (old('offerings') !== null) {
            return old('offerings', []);
        }

        $catalog = WebsiteProductCatalog::forWebsite($website)->get();
        $imagesByKey = $website->images->keyBy('asset_key');

        if ($catalog['items'] !== []) {
            $live = $updater->readOfferingsFromSite($website);
            $liveById = collect($live)->keyBy('id');
            $liveByKey = collect($live)->keyBy(fn ($row) => strtolower($row['name']).'|'.($row['price'] ?? ''));

            return array_map(function (array $item) use ($imagesByKey, $liveById, $liveByKey) {
                $liveOffering = $liveById->get($item['id'])
                    ?? $liveByKey->get(strtolower($item['name']).'|'.($item['price'] ?? ''));

                $imageId = null;
                if (filled($item['image_asset_key'] ?? null) && $imagesByKey->has($item['image_asset_key'])) {
                    $imageId = $imagesByKey->get($item['image_asset_key'])->id;
                }

                return [
                    'name' => $item['name'],
                    'description' => $liveOffering['description'] ?? $item['description'] ?? '',
                    'price' => $item['price'] ?? '',
                    'image_id' => $imageId,
                ];
            }, $catalog['items']);
        }

        $stored = $website->settings['offerings'] ?? [];
        $live = $updater->readOfferingsFromSite($website);

        if ($stored === [] && $live === []) {
            return [['name' => '', 'description' => '', 'price' => '', 'image_id' => null]];
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

            $offerings[] = [
                'name' => $liveOffering['name'] ?? $storedOffering['name'] ?? '',
                'description' => $liveOffering['description'] ?? $storedOffering['description'] ?? '',
                'price' => $liveOffering['price'] ?? $storedOffering['price'] ?? '',
                'image_id' => $imageId,
            ];
        }

        return $offerings;
    }
}
