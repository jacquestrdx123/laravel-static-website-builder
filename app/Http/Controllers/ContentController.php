<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\SiteContentUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Free, instant edits to the business data of an already-generated site:
 * offerings (services/products), tagline, and contact email. Rewrites
 * the annotated elements in the static HTML - no AI call, no credits.
 */
class ContentController extends Controller
{
    public function edit(Request $request, Website $website, SiteContentUpdater $updater): View
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        return view('websites.content', [
            'website' => $website,
            'editable' => $updater->supportsEditing($website),
        ]);
    }

    public function update(Request $request, Website $website, SiteContentUpdater $updater): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

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

                $this->syncImageToSiteAssets($website, $image);

                $nextSort++;
                $validImageIds[] = $image->id;
                $imageId = $image->id;
            }

            $normalizedOfferings[] = [
                'name' => $offering['name'],
                'description' => $offering['description'] ?? null,
                'price' => $offering['price'] ?? null,
                'image_id' => $imageId,
            ];
        }

        $website->update([
            'settings' => array_merge($website->settings, [
                'tagline' => $data['tagline'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'offering_type' => $data['offering_type'],
                'offering_label' => filled($data['offering_label'] ?? null) ? $data['offering_label'] : null,
                'offerings' => $normalizedOfferings,
            ]),
        ]);

        $changed = $updater->apply($website);

        if ($changed === 0) {
            return redirect()->route('websites.content.edit', $website)
                ->with('error', 'Saved, but this site was generated before content editing existed - regenerate it once to enable live updates.');
        }

        return redirect()->route('websites.show', $website)
            ->with('status', 'Content updated on your site - no credits used.');
    }

    private function syncImageToSiteAssets(Website $website, WebsiteImage $image): void
    {
        $sitePath = $website->sitePath();

        if (! File::isDirectory($sitePath)) {
            return;
        }

        File::ensureDirectoryExists($sitePath.'/assets');
        File::copy(
            Storage::disk('local')->path($image->path),
            $sitePath.'/assets/'.$image->assetName()
        );
    }
}
