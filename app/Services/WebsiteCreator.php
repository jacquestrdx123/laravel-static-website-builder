<?php

namespace App\Services;

use App\Jobs\GenerateWebsiteJob;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteImage;
use App\WebsiteBuilder\WebsiteOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Creates a website from the customer builder brief and queues AI generation.
 */
class WebsiteCreator
{
    /**
     * @param  array<string, mixed>  $data  Validated builder fields
     * @param  array{
     *     logo?: UploadedFile|null,
     *     favicon?: UploadedFile|null,
     *     banner?: UploadedFile|null,
     *     gallery_images?: list<UploadedFile|null>,
     *     offerings?: array<int, array{image?: UploadedFile|null}>
     * }  $files
     *
     * @throws RuntimeException when the user lacks credits
     * @throws ValidationException when too many images are uploaded
     */
    public function create(User $user, array $data, array $files = []): Website
    {
        $this->assertImageCountWithinLimit($files);

        $offerings = [];
        $offeringImageKeys = [];
        foreach ($data['offerings'] ?? [] as $index => $offering) {
            if (! filled($offering['name'] ?? null)) {
                continue;
            }
            $offerings[] = $offering;
            $offeringImageKeys[] = $index;
        }

        $user->spendCredits(
            config('sites.generation_cost'),
            'AI generation for "'.$data['name'].'"'
        );

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
            if (($files[$field] ?? null) instanceof UploadedFile) {
                $this->storeUploadedImage($website, $files[$field], $type, $sort++);
            }
        }

        $galleryDescriptions = $data['gallery_descriptions'] ?? [];
        foreach ($files['gallery_images'] ?? [] as $index => $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

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
            $offeringFiles = $files['offerings'] ?? [];

            foreach ($offerings as $offeringIndex => $offering) {
                $requestIndex = $offeringImageKeys[$offeringIndex];
                $imageId = null;
                $upload = $offeringFiles[$requestIndex]['image'] ?? null;

                if ($upload instanceof UploadedFile) {
                    $image = $this->storeUploadedImage(
                        $website,
                        $upload,
                        WebsiteImage::TYPE_PRODUCT,
                        $sort++
                    );
                    $imageId = $image->id;
                }

                $settings['offerings'][$offeringIndex]['image_id'] = $imageId;
            }

            $website->update(['settings' => $settings]);
        }

        $website->refresh()->load('images');
        WebsiteProductCatalog::forWebsite($website)->save(
            WebsiteProductCatalog::forWebsite($website)->buildFromOfferings(
                $website->settings['offerings'] ?? [],
                $website->settings['offering_type'] ?? 'products',
                $website->settings['offering_label'] ?? null,
            )
        );

        GenerateWebsiteJob::dispatch($website->fresh(), $website->settings);

        return $website;
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

    /** @param  array<string, mixed>  $files */
    private function assertImageCountWithinLimit(array $files): void
    {
        $count = 0;

        foreach (['logo', 'favicon', 'banner'] as $field) {
            if (($files[$field] ?? null) instanceof UploadedFile) {
                $count++;
            }
        }

        foreach ($files['gallery_images'] ?? [] as $upload) {
            if ($upload instanceof UploadedFile) {
                $count++;
            }
        }

        foreach ($files['offerings'] ?? [] as $offeringFiles) {
            if (is_array($offeringFiles) && ($offeringFiles['image'] ?? null) instanceof UploadedFile) {
                $count++;
            }
        }

        $max = config('sites.max_images');
        if ($count > $max) {
            throw ValidationException::withMessages([
                'galleryImages' => "You can upload at most {$max} photos in total (logo, favicon, banner, gallery, and product photos combined).",
                'gallery_images' => "You can upload at most {$max} photos in total (logo, favicon, banner, gallery, and product photos combined).",
            ]);
        }
    }

    private function storeUploadedImage(
        Website $website,
        UploadedFile $upload,
        string $type,
        int $sort,
        ?string $description = null
    ): WebsiteImage {
        $path = $upload->store('uploads/'.$website->id, 'local');

        $image = $website->images()->create([
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'type' => $type,
            'description' => $description,
            'mime_type' => $upload->getMimeType(),
            'sort' => $sort,
        ]);

        WebsiteAssetCdn::forWebsite($website)->publish($image);

        return $image;
    }

    /** Validation rules shared by the HTTP controller and Livewire wizard. */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'site_type' => ['required', 'in:'.implode(',', WebsiteOptions::SITE_TYPES)],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['in:'.implode(',', WebsiteOptions::SECTIONS)],
            'style' => ['required', 'in:'.implode(',', WebsiteOptions::STYLES)],
            'color_scheme' => ['required', 'in:'.implode(',', WebsiteOptions::COLOR_SCHEMES)],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'features' => ['nullable', 'array'],
            'features.*' => ['in:'.implode(',', WebsiteOptions::FEATURES)],
            'extra_instructions' => ['nullable', 'string', 'max:2000'],
            'offering_type' => ['required', 'in:'.implode(',', WebsiteOptions::OFFERING_TYPES)],
            'offering_label' => ['nullable', 'string', 'max:50'],
            'ai_elaborate_offerings' => ['nullable', 'boolean'],
            'offerings' => ['nullable', 'array', 'max:'.WebsiteOptions::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
            'generate_favicon_from_logo' => ['nullable', 'boolean'],
            'gallery_descriptions' => ['nullable', 'array'],
            'gallery_descriptions.*' => ['nullable', 'string', 'max:200'],
        ];
    }
}
