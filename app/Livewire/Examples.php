<?php

namespace App\Livewire;

use App\Models\Website;
use App\WebsiteBuilder\WebsiteOptions;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public showcase of demo sites. Deliberately outside the auth middleware —
 * this is the top of the logged-out funnel, so a visitor can see what the
 * product actually produces before being asked to register.
 */
#[Title('Examples')]
class Examples extends Component
{
    public function render()
    {
        $templates = WebsiteOptions::siteTypeTemplates();

        $demos = Website::publicDemos()->get()->map(function (Website $site) use ($templates) {
            $settings = $site->settings ?? [];
            $type = $settings['site_type'] ?? 'business';

            return [
                'name' => $site->name,
                'slug' => $site->slug,
                'tagline' => $settings['tagline'] ?? null,
                'description' => $settings['description'] ?? null,
                'type' => $type,
                'type_label' => $templates[$type]['label'] ?? ucfirst($type),
                'style' => $settings['style'] ?? null,
                'scheme' => $settings['color_scheme'] ?? 'light',
                'accent' => $settings['accent_color'] ?? '#22d3ee',
                'sections' => $settings['sections'] ?? [],
                'offerings' => count($settings['offerings'] ?? []),
                'url' => $site->previewUrl(),
            ];
        });

        return view('livewire.examples', ['demos' => $demos])
            ->extends('layouts.marketing');
    }
}
