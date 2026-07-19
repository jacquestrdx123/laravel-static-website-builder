<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Website;
use App\Services\WebsiteContentVault;
use App\Services\WebsiteCreator;
use App\WebsiteBuilder\WebsiteOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class WebsiteController extends Controller
{
    /** @deprecated Use WebsiteOptions::SITE_TYPES */
    public const SITE_TYPES = WebsiteOptions::SITE_TYPES;

    /** @deprecated Use WebsiteOptions::OFFERING_TYPES */
    public const OFFERING_TYPES = WebsiteOptions::OFFERING_TYPES;

    /** @deprecated Use WebsiteOptions::MAX_OFFERINGS */
    public const MAX_OFFERINGS = WebsiteOptions::MAX_OFFERINGS;

    /** @deprecated Use WebsiteOptions::SECTIONS */
    public const SECTIONS = WebsiteOptions::SECTIONS;

    /** @deprecated Use WebsiteOptions::STYLES */
    public const STYLES = WebsiteOptions::STYLES;

    /** @deprecated Use WebsiteOptions::COLOR_SCHEMES */
    public const COLOR_SCHEMES = WebsiteOptions::COLOR_SCHEMES;

    /** @deprecated Use WebsiteOptions::FEATURES */
    public const FEATURES = WebsiteOptions::FEATURES;

    public function index(Request $request): View
    {
        return view('websites.index', [
            'websites' => $request->user()->websites()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('websites.create');
    }

    public function store(Request $request, WebsiteCreator $creator): RedirectResponse
    {
        $data = $request->validate(array_merge(WebsiteCreator::rules(), [
            'offerings.*.image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'favicon' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
        ]));

        $offeringFiles = [];
        foreach ($request->file('offerings', []) as $index => $offeringFileBag) {
            if (is_array($offeringFileBag) && isset($offeringFileBag['image'])) {
                $offeringFiles[$index] = ['image' => $offeringFileBag['image']];
            }
        }

        try {
            $website = $creator->create($request->user(), $data, [
                'logo' => $request->file('logo'),
                'favicon' => $request->file('favicon'),
                'banner' => $request->file('banner'),
                'gallery_images' => array_values($request->file('gallery_images', [])),
                'offerings' => $offeringFiles,
            ]);
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need more credits to generate a website.');
        }

        return redirect()->route('websites.show', $website)
            ->with('status', 'Your website is being generated.');
    }

    public function show(Request $request, Website $website): View
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        $vault = WebsiteContentVault::forWebsite($website);

        return view('websites.show', [
            'website' => $website,
            'vaultCounts' => [
                'newsletters' => count($vault->listNewsletters()),
                'posters' => count($vault->listPosters()),
                'snapshots' => count($vault->listProductSnapshots()),
            ],
        ]);
    }

    /** Lightweight status endpoint the show page polls while a generation runs. */
    public function status(Request $request, Website $website): JsonResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        return response()->json([
            'status' => $website->status,
            'error' => $website->error,
        ]);
    }

    /** Re-run generation with the same settings (costs a credit). */
    public function regenerate(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_if($website->isBusy(), 409);

        try {
            $request->user()->spendCredits(
                config('sites.generation_cost'),
                'AI regeneration for "'.$website->name.'"'
            );
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need more credits to regenerate this website.');
        }

        $website->update(['status' => Website::STATUS_QUEUED, 'error' => null]);
        GenerateWebsiteJob::dispatch($website, $website->settings);

        return redirect()->route('websites.show', $website)
            ->with('status', 'Your website is being regenerated.');
    }

    public function destroy(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);

        File::deleteDirectory($website->sitePath());
        File::deleteDirectory(config('sites.publish_path').'/'.$website->slug);
        Storage::disk('local')->deleteDirectory('uploads/'.$website->id);
        $website->delete();

        return redirect()->route('dashboard')->with('status', 'Website deleted.');
    }
}
