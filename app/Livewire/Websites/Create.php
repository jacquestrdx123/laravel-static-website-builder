<?php

namespace App\Livewire\Websites;

use App\Services\WebsiteCreator;
use App\WebsiteBuilder\WebsiteOptions;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Title('New website')]
class Create extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $name = '';

    public string $tagline = '';

    public string $description = '';

    public string $contact_email = '';

    public string $site_type = 'business';

    /** @var list<string> */
    public array $sections = ['hero', 'about', 'gallery', 'contact'];

    public string $style = 'minimal';

    public string $color_scheme = 'light';

    public string $accent_color = '#0e7a5f';

    /** @var list<string> */
    public array $features = ['seo_meta', 'smooth_scroll'];

    public string $extra_instructions = '';

    public string $offering_type = 'services';

    public string $offering_label = '';

    public bool $ai_elaborate_offerings = false;

    public bool $generate_favicon_from_logo = false;

    public $logo = null;

    public $favicon = null;

    public $banner = null;

    /** @var list<array{file: mixed, description: string}> */
    public array $galleryRows = [
        ['file' => null, 'description' => ''],
    ];

    /** @var list<array{name: string, description: string, price: string, image: mixed}> */
    public array $offerings = [
        ['name' => '', 'description' => '', 'price' => '', 'image' => null],
    ];

    public function updatedSiteType(string $value): void
    {
        $template = WebsiteOptions::siteTypeTemplates()[$value] ?? null;
        if ($template === null) {
            return;
        }

        $this->sections = $template['default_sections'];
        $this->offering_type = $template['default_offering_type'];
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'site_type' => ['required', 'in:'.implode(',', WebsiteOptions::SITE_TYPES)],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['required', 'string', 'max:2000'],
                'tagline' => ['nullable', 'string', 'max:200'],
                'contact_email' => ['nullable', 'email', 'max:255'],
            ]);
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 4) {
            return;
        }

        if ($step > 1 && $this->step === 1) {
            $this->validate([
                'site_type' => ['required', 'in:'.implode(',', WebsiteOptions::SITE_TYPES)],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['required', 'string', 'max:2000'],
            ]);
        }

        $this->step = $step;
    }

    public function addGalleryRow(): void
    {
        if (count($this->galleryRows) >= (int) config('sites.max_images')) {
            return;
        }

        $this->galleryRows[] = ['file' => null, 'description' => ''];
    }

    public function removeGalleryRow(int $index): void
    {
        if (count($this->galleryRows) === 1) {
            $this->galleryRows[0] = ['file' => null, 'description' => ''];

            return;
        }

        unset($this->galleryRows[$index]);
        $this->galleryRows = array_values($this->galleryRows);
    }

    public function addOffering(): void
    {
        if (count($this->offerings) >= WebsiteOptions::MAX_OFFERINGS) {
            return;
        }

        $this->offerings[] = ['name' => '', 'description' => '', 'price' => '', 'image' => null];
    }

    public function removeOffering(int $index): void
    {
        if (count($this->offerings) === 1) {
            $this->offerings[0] = ['name' => '', 'description' => '', 'price' => '', 'image' => null];

            return;
        }

        unset($this->offerings[$index]);
        $this->offerings = array_values($this->offerings);
    }

    public function save(WebsiteCreator $creator): mixed
    {
        $validated = $this->validate([
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
            'generate_favicon_from_logo' => ['nullable', 'boolean'],
            'offerings' => ['nullable', 'array', 'max:'.WebsiteOptions::MAX_OFFERINGS],
            'offerings.*.name' => ['nullable', 'string', 'max:100'],
            'offerings.*.description' => ['nullable', 'string', 'max:500'],
            'offerings.*.price' => ['nullable', 'string', 'max:50'],
            'offerings.*.image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'favicon' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'galleryRows.*.file' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
            'galleryRows.*.description' => ['nullable', 'string', 'max:200'],
        ]);

        $galleryImages = [];
        $galleryDescriptions = [];
        foreach ($this->galleryRows as $row) {
            if ($row['file'] ?? null) {
                $galleryImages[] = $row['file'];
                $galleryDescriptions[] = $row['description'] ?? '';
            }
        }

        $offeringFiles = [];
        foreach ($this->offerings as $index => $offering) {
            if ($offering['image'] ?? null) {
                $offeringFiles[$index] = ['image' => $offering['image']];
            }
        }

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'tagline' => $validated['tagline'] ?: null,
            'contact_email' => $validated['contact_email'] ?: null,
            'site_type' => $validated['site_type'],
            'sections' => $validated['sections'],
            'style' => $validated['style'],
            'color_scheme' => $validated['color_scheme'],
            'accent_color' => $validated['accent_color'] ?: null,
            'features' => $validated['features'] ?? [],
            'extra_instructions' => $validated['extra_instructions'] ?: null,
            'offering_type' => $validated['offering_type'],
            'offering_label' => $validated['offering_label'] ?: null,
            'ai_elaborate_offerings' => $validated['ai_elaborate_offerings'] ?? false,
            'generate_favicon_from_logo' => $validated['generate_favicon_from_logo'] ?? false,
            'offerings' => $this->offerings,
            'gallery_descriptions' => $galleryDescriptions,
        ];

        try {
            $website = $creator->create(auth()->user(), $data, [
                'logo' => $this->logo,
                'favicon' => $this->favicon,
                'banner' => $this->banner,
                'gallery_images' => $galleryImages,
                'offerings' => $offeringFiles,
            ]);
        } catch (RuntimeException) {
            session()->flash('error', 'You need more credits to generate a website.');

            return $this->redirect(route('billing.index'), navigate: true);
        }

        session()->flash('status', 'Your website is being generated.');

        return $this->redirect(route('websites.show', $website), navigate: true);
    }

    public function render()
    {
        return view('livewire.websites.create', [
            'templates' => WebsiteOptions::siteTypeTemplates(),
            'allSections' => WebsiteOptions::SECTIONS,
            'styles' => WebsiteOptions::STYLES,
            'colorSchemes' => WebsiteOptions::COLOR_SCHEMES,
            'featureLabels' => WebsiteOptions::featureLabels(),
            'offeringTypes' => WebsiteOptions::OFFERING_TYPES,
            'maxOfferings' => WebsiteOptions::MAX_OFFERINGS,
            'maxPhotos' => (int) config('sites.max_images'),
            'generationCost' => config('sites.generation_cost'),
        ])->extends('layouts.app');
    }
}
