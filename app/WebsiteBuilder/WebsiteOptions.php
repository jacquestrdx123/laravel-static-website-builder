<?php

namespace App\WebsiteBuilder;

/**
 * Shared customer-builder constants and per-site-type template metadata.
 *
 * Each site type maps to a structural blueprint under
 * resources/prompts/blueprints/{type}.md that shapes AI generation.
 */
final class WebsiteOptions
{
    public const SITE_TYPES = ['business', 'portfolio', 'restaurant', 'landing', 'personal', 'event'];

    public const OFFERING_TYPES = ['services', 'products'];

    public const MAX_OFFERINGS = 12;

    public const SECTIONS = ['hero', 'about', 'services', 'gallery', 'testimonials', 'pricing', 'faq', 'contact'];

    public const STYLES = ['minimal', 'bold', 'elegant', 'playful', 'corporate'];

    public const COLOR_SCHEMES = ['light', 'dark', 'auto'];

    public const FEATURES = ['smooth_scroll', 'animations', 'sticky_header', 'back_to_top', 'seo_meta', 'contact_form'];

    /**
     * Customer-facing template cards for the create wizard.
     *
     * @return array<string, array{label: string, summary: string, best_for: string, default_sections: list<string>, default_offering_type: string}>
     */
    public static function siteTypeTemplates(): array
    {
        return [
            'business' => [
                'label' => 'Business',
                'summary' => 'Credibility-first company site: hero, about, offerings, proof, and a clear contact path.',
                'best_for' => 'Local services, agencies, professional firms',
                'default_sections' => ['hero', 'about', 'services', 'testimonials', 'contact'],
                'default_offering_type' => 'services',
            ],
            'portfolio' => [
                'label' => 'Portfolio',
                'summary' => 'Work-first layout that leads with projects and case studies, then a short about and contact.',
                'best_for' => 'Designers, photographers, freelancers',
                'default_sections' => ['hero', 'gallery', 'about', 'contact'],
                'default_offering_type' => 'services',
            ],
            'restaurant' => [
                'label' => 'Restaurant',
                'summary' => 'Appetite-led site with menu items, atmosphere photos, and visit/reserve calls to action.',
                'best_for' => 'Cafés, restaurants, food businesses',
                'default_sections' => ['hero', 'about', 'services', 'gallery', 'contact'],
                'default_offering_type' => 'products',
            ],
            'landing' => [
                'label' => 'Landing',
                'summary' => 'Single-purpose conversion page: promise, proof, offer details, FAQ, and one primary CTA.',
                'best_for' => 'Product launches, lead capture, campaigns',
                'default_sections' => ['hero', 'about', 'pricing', 'faq', 'contact'],
                'default_offering_type' => 'products',
            ],
            'personal' => [
                'label' => 'Personal',
                'summary' => 'Warm personal brand page: story, highlights, selected work, and an easy way to reach you.',
                'best_for' => 'Creators, coaches, personal brands',
                'default_sections' => ['hero', 'about', 'gallery', 'contact'],
                'default_offering_type' => 'services',
            ],
            'event' => [
                'label' => 'Event',
                'summary' => 'What / when / where up front, with schedule details, venue info, and an RSVP-style CTA.',
                'best_for' => 'Conferences, weddings, launches, meetups',
                'default_sections' => ['hero', 'about', 'faq', 'contact'],
                'default_offering_type' => 'services',
            ],
        ];
    }

    /** @return list<string> */
    public static function siteTypeKeys(): array
    {
        return self::SITE_TYPES;
    }

    public static function featureLabels(): array
    {
        return [
            'smooth_scroll' => 'Smooth scrolling',
            'animations' => 'Subtle animations',
            'sticky_header' => 'Sticky header',
            'back_to_top' => 'Back-to-top button',
            'seo_meta' => 'SEO meta tags',
            'contact_form' => 'Contact form',
        ];
    }
}
