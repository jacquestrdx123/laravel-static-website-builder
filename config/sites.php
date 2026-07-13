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

];
