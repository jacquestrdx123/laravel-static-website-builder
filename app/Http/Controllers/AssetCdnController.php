<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\WebsiteAssetCdn;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetCdnController extends Controller
{
    /**
     * Public, cacheable asset delivery for customer website images.
     *
     * URL shape: /cdn/{website}/{asset_key}
     */
    public function show(Request $request, Website $website, string $assetKey): Response
    {
        /** @var WebsiteImage|null $image */
        $image = $website->images()->where('asset_key', $assetKey)->first();

        abort_if($image === null, 404);

        $cdn = WebsiteAssetCdn::forWebsite($website);
        $extension = strtolower(pathinfo($image->path, PATHINFO_EXTENSION) ?: 'jpg');
        $path = $cdn->absolutePath($assetKey, $extension);

        if (! is_file($path)) {
            if (! $image->existsOnDisk()) {
                abort(404);
            }

            $cdn->publish($image);
            $path = $cdn->absolutePath($assetKey, $extension);
        }

        return response()->file($path, [
            'Content-Type' => $image->mime_type,
            'Cache-Control' => 'public, max-age='.config('sites.cdn_cache_max_age').', immutable',
        ]);
    }
}
