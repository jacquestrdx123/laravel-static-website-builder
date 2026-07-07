<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CaddyController extends Controller
{
    /**
     * Caddy on_demand_tls "ask" endpoint.
     *
     * Caddy calls GET /caddy/allowed?domain=example.com before issuing a
     * certificate. Return 200 to allow, 4xx to deny. This stops the server
     * minting certificates for hostnames that aren't published customer sites.
     */
    public function allowed(Request $request): Response
    {
        $domain = strtolower((string) $request->query('domain', ''));

        if ($domain === '') {
            return response('missing domain', 400);
        }

        $wildcard = '.'.config('sites.domain');

        if (Str::endsWith($domain, $wildcard)) {
            $slug = Str::beforeLast($domain, $wildcard);

            $exists = Website::where('slug', $slug)
                ->where('status', Website::STATUS_PUBLISHED)
                ->exists();

            return $exists ? response('ok') : response('unknown site', 404);
        }

        $exists = Website::where('custom_domain', $domain)
            ->where('status', Website::STATUS_PUBLISHED)
            ->exists();

        return $exists ? response('ok') : response('unknown domain', 404);
    }
}
