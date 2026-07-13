<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteImage;
use App\Services\WebsiteAssetCdn;
use App\Services\WebsiteProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_persists_to_mysql_and_mirrors_settings(): void
    {
        $website = $this->makeWebsite();

        $catalog = WebsiteProductCatalog::forWebsite($website)->save(
            WebsiteProductCatalog::forWebsite($website)->buildFromOfferings(
                [
                    ['name' => 'Widget', 'description' => 'Nice', 'price' => 'R10', 'image_id' => null],
                ],
                'products',
                'Shop',
            )
        );

        $this->assertSame(1, $catalog['schema_version']);
        $this->assertCount(1, $website->fresh()->product_catalog['items']);
        $this->assertSame('Widget', $website->settings['offerings'][0]['name']);
    }

    public function test_catalog_export_resolves_cdn_image_urls(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $website = $this->makeWebsite();
        $path = 'uploads/'.$website->id.'/product.jpg';
        Storage::disk('local')->put($path, 'bytes');

        $image = $website->images()->create([
            'path' => $path,
            'original_name' => 'product.jpg',
            'type' => WebsiteImage::TYPE_PRODUCT,
            'mime_type' => 'image/jpeg',
            'sort' => 0,
        ]);

        WebsiteAssetCdn::forWebsite($website)->publish($image);

        WebsiteProductCatalog::forWebsite($website)->save(
            WebsiteProductCatalog::forWebsite($website)->buildFromOfferings(
                [['name' => 'Widget', 'description' => '', 'price' => 'R10', 'image_id' => $image->id]],
            )
        );

        ['catalog' => $export] = WebsiteProductCatalog::forWebsite($website->fresh())->forSiteExport();

        $this->assertStringContainsString('/cdn/'.$website->id.'/', $export['items'][0]['image_url']);
    }

    public function test_write_to_site_emits_catalog_json(): void
    {
        $website = $this->makeWebsite();
        File::ensureDirectoryExists($website->sitePath());

        WebsiteProductCatalog::forWebsite($website)->save(
            WebsiteProductCatalog::forWebsite($website)->buildFromOfferings(
                [['name' => 'Tea', 'description' => '', 'price' => 'R20', 'image_id' => null]],
            )
        );

        WebsiteProductCatalog::forWebsite($website)->writeToSite($website->sitePath());

        $this->assertFileExists($website->sitePath().'/catalog.json');
        $json = json_decode(File::get($website->sitePath().'/catalog.json'), true);
        $this->assertSame('Tea', $json['items'][0]['name']);

        File::deleteDirectory($website->sitePath());
    }

    private function makeWebsite(): Website
    {
        $user = User::factory()->create();

        return $user->websites()->create([
            'name' => 'Catalog Shop',
            'slug' => 'catalog-shop',
            'status' => Website::STATUS_READY,
            'settings' => ['offering_type' => 'products', 'offerings' => []],
        ]);
    }
}
