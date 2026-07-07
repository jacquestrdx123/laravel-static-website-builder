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

    // Maximum number of images a customer may attach to one website.
    'max_images' => (int) env('SITES_MAX_IMAGES', 10),

];
