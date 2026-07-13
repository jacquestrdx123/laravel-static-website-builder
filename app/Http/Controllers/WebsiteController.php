<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\WebsiteContentVault;
use RuntimeException;

class WebsiteController extends Controller
{
    public const SITE_TYPES = ['business', 'portfolio', 'restaurant', 'landing', 'personal', 'event'];

    public const OFFERING_TYPES = ['services', 'products'];

    public const MAX_OFFERINGS = 12;

    public const SECTIONS = ['hero', 'about', 'services', 'gallery', 'testimonials', 'pricing', 'faq', 'contact'];

    public const STYLES = ['minimal', 'bold', 'elegant', 'playful', 'corporate'];

    public const COLOR_SCHEMES = ['light', 'dark', 'auto'];

    public const FEATURES = ['smooth_scroll', 'animations', 'sticky_header', 'back_to_top', 'seo_meta', 'contact_form'];

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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'site_type' => ['required', 'in:'.implode(',', self::SITE_TYPES)],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['in:'.implode(',', self::SECTIONS)],
            'style' => ['required', 'in:'.implode(',', self::STYLES)],
            'color_scheme' => ['required', 'in:'.implode(',', self::COLOR_SCHEMES)],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'features' => ['nullable', 'array'],
            'features.*' => ['in:'.implode(',', self::FEATURES)],
            'extra_instructions' => ['nullable', 'string', 'max:2000'],
            'offering_type' => ['required', 'in:'.implode(',', self::OFFERING_TYPES)],
            'offering_label' => ['nullable', 'string', 'max:50'],
            'ai_elaborate_offerings' => ['nullable', 'boolean'],
            'offerings' => ['nullable', 'array', 'max:'.self::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
            'offerings.*.image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'favicon' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'generate_favicon_from_logo' => ['nullable', 'boolean'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'gallery_descriptions' => ['nullable', 'array'],
            'gallery_descriptions.*' => ['nullable', 'string', 'max:200'],
        ]);

        $this->assertImageCountWithinLimit($request);

        // Keep request indices so per-offering file uploads line up after filtering.
        $offerings = [];
        $offeringImageKeys = [];
        foreach ($data['offerings'] ?? [] as $index => $offering) {
            if (! filled($offering['name'] ?? null)) {
                continue;
            }
            $offerings[] = $offering;
            $offeringImageKeys[] = $index;
        }

        $user = $request->user();

        try {
            $user->spendCredits(
                config('sites.generation_cost'),
                'AI generation for "'.$data['name'].'"'
            );
        } catch (RuntimeException) {
            return redirect()->route('billing.index')
                ->with('error', 'You need more credits to generate a website.');
        }

        $website = $user->websites()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'status' => Website::STATUS_QUEUED,
            'settings' => [
                'description' => $data['description'],
                'tagline' => $data['tagline'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'site_type' => $data['site_type'],
                'sections' => array_values($data['sections']),
                'style' => $data['style'],
                'color_scheme' => $data['color_scheme'],
                'accent_color' => $data['accent_color'] ?? null,
                'features' => array_values($data['features'] ?? []),
                'offering_type' => $data['offering_type'],
                'offering_label' => filled($data['offering_label'] ?? null) ? $data['offering_label'] : null,
                'ai_elaborate_offerings' => (bool) ($data['ai_elaborate_offerings'] ?? false),
                'generate_favicon_from_logo' => (bool) ($data['generate_favicon_from_logo'] ?? false),
                'offerings' => array_map(fn ($offering) => [
                    'name' => $offering['name'],
                    'description' => $offering['description'] ?? null,
                    'price' => $offering['price'] ?? null,
                    'image_id' => null,
                ], $offerings),
                'extra_instructions' => $data['extra_instructions'] ?? null,
            ],
        ]);

        $sort = 0;

        foreach ([
            'logo' => WebsiteImage::TYPE_LOGO,
            'favicon' => WebsiteImage::TYPE_FAVICON,
            'banner' => WebsiteImage::TYPE_BANNER,
        ] as $field => $type) {
            if ($request->hasFile($field)) {
                $this->storeUploadedImage($website, $request->file($field), $type, $sort++);
            }
        }

        $galleryDescriptions = $data['gallery_descriptions'] ?? [];
        foreach ($request->file('gallery_images', []) as $index => $upload) {
            $this->storeUploadedImage(
                $website,
                $upload,
                WebsiteImage::TYPE_GALLERY,
                $sort++,
                $galleryDescriptions[$index] ?? null
            );
        }

        if ($offerings !== []) {
            $settings = $website->settings;

            foreach ($offerings as $offeringIndex => $offering) {
                $requestIndex = $offeringImageKeys[$offeringIndex];
                $imageId = null;

                if ($request->hasFile("offerings.$requestIndex.image")) {
                    $image = $this->storeUploadedImage(
                        $website,
                        $request->file("offerings.$requestIndex.image"),
                        WebsiteImage::TYPE_PRODUCT,
                        $sort++
                    );
                    $imageId = $image->id;
                }

                $settings['offerings'][$offeringIndex]['image_id'] = $imageId;
            }

            $website->update(['settings' => $settings]);
        }

        GenerateWebsiteJob::dispatch($website, $website->settings);

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
            'hasEditingSubscription' => $website->hasActiveEditingSubscription(),
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

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;

        while (Website::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    private function assertImageCountWithinLimit(Request $request): void
    {
        $count = 0;

        foreach (['logo', 'favicon', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                $count++;
            }
        }

        $count += count($request->file('gallery_images', []));

        foreach ($request->file('offerings', []) as $offeringFiles) {
            if (is_array($offeringFiles) && isset($offeringFiles['image']) && $offeringFiles['image']) {
                $count++;
            }
        }

        $max = config('sites.max_images');
        if ($count > $max) {
            throw ValidationException::withMessages([
                'gallery_images' => "You can upload at most {$max} photos in total (logo, favicon, banner, gallery, and product photos combined).",
            ]);
        }
    }

    private function storeUploadedImage(
        Website $website,
        $upload,
        string $type,
        int $sort,
        ?string $description = null
    ): WebsiteImage {
        $path = $upload->store('uploads/'.$website->id, 'local');

        return $website->images()->create([
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'type' => $type,
            'description' => $description,
            'mime_type' => $upload->getMimeType(),
            'sort' => $sort,
        ]);
    }
}
