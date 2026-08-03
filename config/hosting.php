<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host provisioner driver
    |--------------------------------------------------------------------------
    |
    | Phase 1–2 production: "filesystem" — PublishedSiteHost already writes
    | /srv/websites trees and domain symlinks; the provisioner is a no-op hook
    | for future Nginx snippet reloads.
    |
    | Local / tests: "null"
    | Future optional: "nginx_snippets"
    |
    */

    'driver' => env('HOSTING_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Caddy on-demand TLS ask URL
    |--------------------------------------------------------------------------
    |
    | Documented for ops and mirrored in deploy/caddy.edge.Caddyfile.
    | Caddy must call this on loopback before minting customer certs.
    |
    */

    'caddy_ask_url' => env('CADDY_ASK_URL', 'http://127.0.0.1:8080/caddy/allowed'),

    /*
    |--------------------------------------------------------------------------
    | Nginx customer snippets (Phase 3 only — unused in Phase 1)
    |--------------------------------------------------------------------------
    */

    'nginx_customers_path' => env('NGINX_CUSTOMERS_PATH', '/etc/nginx/sites-available/customers'),

];
