<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Models\User;
use App\Models\Website;
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
            'images' => [UploadedFile::fake()->image('shop.jpg', 640, 480)],
        ]);

        $website = Website::first();
        $response->assertRedirect(route('websites.show', $website));

        $this->assertSame(1, $user->fresh()->ai_credits);
        $this->assertSame(Website::STATUS_QUEUED, $website->status);
        $this->assertCount(1, $website->images);
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
        ]);

        $response->assertRedirect(route('billing.index'));
        $this->assertSame(0, Website::count());
        Queue::assertNothingPushed();
    }

    public function test_preview_is_owner_only_and_blocks_path_traversal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $website = $owner->websites()->create([
            'name' => 'Site',
            'slug' => 'site',
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', '<h1>Hello</h1>');
        File::put($website->sitePath().'/styles.css', 'body{}');

        // Bare /preview redirects to /preview/index.html so that relative
        // asset URLs in the document resolve inside the preview route.
        $this->actingAs($owner)->get(route('websites.preview', $website))
            ->assertRedirect(route('websites.preview', [$website, 'index.html']));

        $this->actingAs($owner)->get(route('websites.preview', [$website, 'index.html']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        $this->actingAs($owner)->get(route('websites.preview', [$website, 'styles.css']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');

        $this->actingAs($other)->get(route('websites.preview', $website))->assertForbidden();

        $this->actingAs($owner)
            ->get('/websites/'.$website->id.'/preview/..%2F..%2F.env')
            ->assertNotFound();

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
