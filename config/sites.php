<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer Site Hosting
    |--------------------------------------------------------------------------
    |
    | Generated sites are written to storage/app/private/sites/{slug} while in
    | preview. Publishing copies the site into the publish_path, which Caddy
    | serves per-hostname (see deploy/Caddyfile). "domain" is the wildcard
    | subdomain zone customers get for free, e.g. {slug}.sites.example.com.
    |
    */

    'domain' => env('SITES_DOMAIN', 'sites.localhost'),

    'publish_path' => env('SITES_PUBLISH_PATH', storage_path('app/published')),

    // How many credits one AI generation costs.
    'generation_cost' => (int) env('SITES_GENERATION_COST', 1),

    // 1 credit = this many cents of registrar/list price (2000 = R20.00 per credit).
    'credit_unit_cents' => (int) env('CREDIT_UNIT_CENTS', 2000),

    // Fallback when HostAfrica pricing is unavailable.
    'domain_default_credits' => (int) env('DOMAIN_DEFAULT_CREDITS', 5),

    // Extra credits per optional domain add-on (per order).
    'domain_addon_credits' => [
        'dnsmanagement' => (int) env('DOMAIN_ADDON_DNS_CREDITS', 1),
        'emailforwarding' => (int) env('DOMAIN_ADDON_EMAIL_CREDITS', 1),
        'idprotection' => (int) env('DOMAIN_ADDON_ID_PROTECT_CREDITS', 2),
    ],

    // Maximum number of images a customer may attach to one website.
    'max_images' => (int) env('SITES_MAX_IMAGES', 10),

    // Private per-website vault (SQLite + files). Never web-accessible.
    'website_data_path' => env('WEBSITE_DATA_PATH', storage_path('app/website-data')),

    /*
    |--------------------------------------------------------------------------
    | Asset CDN
    |--------------------------------------------------------------------------
    |
    | Customer uploads are published here with stable URLs so generated static
    | sites can reference images that survive product edits without copying
    | files into each site rebuild. CDN_BASE_URL is usually the builder app
    | origin (app.example.com); customer sites on other hostnames load assets
    | from it cross-origin.
    |
    */

    'cdn_url' => rtrim(env('CDN_BASE_URL', env('APP_URL', 'http://localhost')), '/'),

    'cdn_disk' => env('CDN_DISK', 'public'),

    'cdn_path_prefix' => 'cdn',

    // Browser cache for immutable CDN assets (asset_key never changes).
    'cdn_cache_max_age' => (int) env('CDN_CACHE_MAX_AGE', 31536000),

    'editing_subscription_price' => env('EDITING_SUBSCRIPTION_PRICE', 'R299/year'),
    'editing_subscription_years' => (int) env('EDITING_SUBSCRIPTION_YEARS', 1),
    'newsletter_generation_cost' => (int) env('NEWSLETTER_GENERATION_COST', 2),
    'poster_generation_cost' => (int) env('POSTER_GENERATION_COST', 3),

    'poster_formats' => [
        'instagram_square' => ['label' => 'Instagram square', 'width' => 1080, 'height' => 1080],
        'facebook_landscape' => ['label' => 'Facebook landscape', 'width' => 1200, 'height' => 630],
        'story' => ['label' => 'Story', 'width' => 1080, 'height' => 1920],
    ],

];
