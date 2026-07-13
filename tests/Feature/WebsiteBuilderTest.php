<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_grants_welcome_credit(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jacques',
            'email' => 'jacques@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(1, User::firstWhere('email', 'jacques@example.com')->ai_credits);
    }

    public function test_creating_a_website_spends_a_credit_and_queues_generation(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create(['ai_credits' => 2]);

        $response = $this->actingAs($user)->post('/websites', [
            'name' => 'My Bakery',
            'description' => 'A family bakery in Stellenbosch.',
            'site_type' => 'business',
            'sections' => ['hero', 'about', 'contact'],
            'style' => 'minimal',
            'color_scheme' => 'light',
            'features' => ['seo_meta'],
            'offering_type' => 'products',
            'offering_label' => 'Our Bakes',
            'ai_elaborate_offerings' => '1',
            'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
            'offerings' => [
                ['name' => 'Sourdough loaf', 'description' => 'Naturally leavened', 'price' => 'R65', 'image' => UploadedFile::fake()->image('loaf.jpg', 400, 400)],
                ['name' => '', 'description' => '', 'price' => ''],
                ['name' => 'Wedding cake', 'description' => '', 'price' => 'from R2,500'],
            ],
            'gallery_images' => [UploadedFile::fake()->image('shop.jpg', 640, 480)],
            'gallery_descriptions' => ['Our storefront'],
        ]);

        $website = Website::first();
        $response->assertRedirect(route('websites.show', $website));

        $this->assertSame(1, $user->fresh()->ai_credits);
        $this->assertSame(Website::STATUS_QUEUED, $website->status);
        $this->assertCount(3, $website->images);

        // Offerings are stored with empty rows filtered out.
        $this->assertSame('products', $website->settings['offering_type']);
        $this->assertSame('Our Bakes', $website->settings['offering_label']);
        $this->assertTrue($website->settings['ai_elaborate_offerings']);
        $this->assertCount(2, $website->settings['offerings']);
        $this->assertSame('Sourdough loaf', $website->settings['offerings'][0]['name']);
        $this->assertSame('from R2,500', $website->settings['offerings'][1]['price']);
        $this->assertNotNull($website->settings['offerings'][0]['image_id']);
        $this->assertNull($website->settings['offerings'][1]['image_id']);

        $types = $website->images->pluck('type')->all();
        $this->assertContains('banner', $types);
        $this->assertContains('gallery', $types);
        $this->assertContains('product', $types);

        Queue::assertPushed(GenerateWebsiteJob::class);
    }

    public function test_creating_a_website_without_credits_is_rejected(): void
    {
        Queue::fake();

        $user = User::factory()->create(['ai_credits' => 0]);

        $response = $this->actingAs($user)->post('/websites', [
            'name' => 'My Bakery',
            'description' => 'A bakery.',
            'site_type' => 'business',
            'sections' => ['hero'],
            'style' => 'minimal',
            'color_scheme' => 'light',
            'offering_type' => 'services',
        ]);

        $response->assertRedirect(route('billing.index'));
        $this->assertSame(0, Website::count());
        Queue::assertNothingPushed();
    }

    public function test_generated_sites_live_on_the_public_disk(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $website = $owner->websites()->create([
            'name' => 'Site',
            'slug' => 'site',
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        // Sites are written to the public disk so the web server serves
        // previews directly through the storage:link symlink.
        $this->assertSame(
            Storage::disk('public')->path('sites/site'),
            $website->sitePath()
        );
        $this->assertStringEndsWith('/storage/sites/site/index.html', $website->previewUrl());

        // The show page embeds the direct static URL (owner only).
        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', '<h1>Hello</h1>');

        $this->actingAs($owner)
            ->get(route('websites.show', $website))
            ->assertOk()
            ->assertSee('/storage/sites/site/index.html');

        $this->actingAs($other)->get(route('websites.show', $website))->assertForbidden();

        File::deleteDirectory($website->sitePath());
    }

    public function test_caddy_ask_endpoint_only_allows_published_hostnames(): void
    {
        config(['sites.domain' => 'sites.example.com']);

        $user = User::factory()->create();
        $user->websites()->create([
            'name' => 'Live', 'slug' => 'live',
            'status' => Website::STATUS_PUBLISHED, 'settings' => [],
        ]);
        $user->websites()->create([
            'name' => 'Draft', 'slug' => 'draft',
            'status' => Website::STATUS_READY, 'settings' => [],
        ]);

        $this->get('/caddy/allowed?domain=live.sites.example.com')->assertOk();
        $this->get('/caddy/allowed?domain=draft.sites.example.com')->assertNotFound();
        $this->get('/caddy/allowed?domain=evil.example.net')->assertNotFound();
        $this->get('/caddy/allowed')->assertBadRequest();
    }

    public function test_content_edits_rewrite_the_static_site_without_credits(): void
    {
        $owner = User::factory()->create(['ai_credits' => 0]);

        $website = $owner->websites()->create([
            'name' => 'Shop',
            'slug' => 'shop',
            'status' => Website::STATUS_READY,
            'settings' => ['offering_type' => 'products', 'offerings' => []],
        ]);

        $website->subscriptions()->create([
            'user_id' => $owner->id,
            'type' => \App\Models\WebsiteSubscription::TYPE_MANUAL_EDITING,
            'status' => \App\Models\WebsiteSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'note' => '[test]',
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html',
            '<html><body><ul><li data-offering="1"><span data-field="name">Old</span>'
            .'<span data-field="description"></span><span data-field="price">R1</span></li></ul></body></html>');

        $response = $this->actingAs($owner)->post(route('websites.content.update', $website), [
            'offering_type' => 'products',
            'offerings' => [['name' => 'New product', 'description' => 'Nice', 'price' => 'R500']],
        ]);

        $response->assertRedirect(route('websites.show', $website));

        $html = File::get($website->sitePath().'/index.html');
        $this->assertStringContainsString('New product', $html);
        $this->assertStringContainsString('R500', $html);
        $this->assertStringNotContainsString('Old', $html);

        // Settings updated, and no credits were involved at any point.
        $this->assertSame('New product', $website->fresh()->settings['offerings'][0]['name']);
        $this->assertSame(0, $owner->fresh()->ai_credits);

        File::deleteDirectory($website->sitePath());
    }

    public function test_content_edit_page_shows_photos_and_live_descriptions(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();

        $website = $owner->websites()->create([
            'name' => 'Gallery Shop',
            'slug' => 'gallery-shop',
            'status' => Website::STATUS_READY,
            'settings' => [
                'offering_type' => 'products',
                'offerings' => [
                    ['name' => 'Mug', 'description' => 'Stored short note', 'price' => 'R120', 'image_id' => null],
                ],
            ],
        ]);

        $website->subscriptions()->create([
            'user_id' => $owner->id,
            'type' => \App\Models\WebsiteSubscription::TYPE_MANUAL_EDITING,
            'status' => \App\Models\WebsiteSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'note' => '[test]',
        ]);

        $galleryPath = UploadedFile::fake()->image('gallery.jpg')->store('uploads/'.$website->id, 'local');
        $productPath = UploadedFile::fake()->image('product.jpg')->store('uploads/'.$website->id, 'local');

        $galleryImage = $website->images()->create([
            'path' => $galleryPath,
            'original_name' => 'gallery.jpg',
            'type' => WebsiteImage::TYPE_GALLERY,
            'description' => 'Our storefront at sunset',
            'mime_type' => 'image/jpeg',
            'sort' => 0,
        ]);

        $productImage = $website->images()->create([
            'path' => $productPath,
            'original_name' => 'product.jpg',
            'type' => WebsiteImage::TYPE_PRODUCT,
            'description' => 'Handmade ceramic mug',
            'mime_type' => 'image/jpeg',
            'sort' => 1,
        ]);

        $website->update([
            'settings' => array_merge($website->settings, [
                'offerings' => [
                    [
                        'name' => 'Mug',
                        'description' => 'Stored short note',
                        'price' => 'R120',
                        'image_id' => $productImage->id,
                    ],
                ],
            ]),
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', <<<'HTML'
            <html><body><ul>
                <li data-offering="1">
                    <span data-field="name">Mug</span>
                    <span data-field="description">AI-crafted description for the handmade mug</span>
                    <span data-field="price">R120</span>
                    <img data-field="image" src="assets/image-2.jpg" alt="Mug">
                </li>
            </ul></body></html>
            HTML);

        $this->actingAs($owner)
            ->get(route('websites.content.edit', $website))
            ->assertOk()
            ->assertSee('Your photos')
            ->assertSee('Our storefront at sunset')
            ->assertSee('Handmade ceramic mug')
            ->assertSee('AI-crafted description for the handmade mug')
            ->assertSee(route('websites.images.show', [$website, $galleryImage]), false);

        $this->actingAs($owner)
            ->get(route('websites.images.show', [$website, $galleryImage]))
            ->assertOk();

        File::deleteDirectory($website->sitePath());
    }

    public function test_billing_stub_adds_credits(): void
    {
        $user = User::factory()->create(['ai_credits' => 0]);

        $this->actingAs($user)
            ->post('/billing/purchase', ['credits' => 5])
            ->assertRedirect(route('billing.index'));

        $this->assertSame(5, $user->fresh()->ai_credits);
        $this->assertSame(1, $user->creditTransactions()->count());
    }
}
