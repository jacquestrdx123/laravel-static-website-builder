<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\WebsiteAssetCdn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetCdnTest extends TestCase
{
    use RefreshDatabase;

    public function test_cdn_route_serves_published_asset_with_cache_headers(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $website = $user->websites()->create([
            'name' => 'CDN Shop',
            'slug' => 'cdn-shop',
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        $path = 'uploads/'.$website->id.'/photo.jpg';
        Storage::disk('local')->put($path, 'jpeg-bytes');

        $image = $website->images()->create([
            'path' => $path,
            'original_name' => 'photo.jpg',
            'type' => WebsiteImage::TYPE_GALLERY,
            'mime_type' => 'image/jpeg',
            'sort' => 0,
        ]);

        WebsiteAssetCdn::forWebsite($website)->publish($image);

        $response = $this->get(route('cdn.asset', [$website, $image->asset_key]));

        $response->assertOk();
        $this->assertStringContainsString('max-age='.config('sites.cdn_cache_max_age'), $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
    }
}
