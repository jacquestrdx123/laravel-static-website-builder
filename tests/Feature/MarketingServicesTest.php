<?php

namespace Tests\Feature;

use App\Jobs\GenerateNewsletterJob;
use App\Jobs\GeneratePosterJob;
use App\Mail\WebsiteNewsletter;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteSubscription;
use App\Services\NewsletterGenerator;
use App\Services\PosterExporter;
use App\Services\PosterGenerator;
use App\Services\PublishedSiteHost;
use App\Services\WebsiteContentVault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MarketingServicesTest extends TestCase
{
    use RefreshDatabase;

    private string $vaultRoot;

    private string $publishRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vaultRoot = storage_path('framework/testing/marketing-vault');
        $this->publishRoot = storage_path('framework/testing/published');
        config([
            'sites.website_data_path' => $this->vaultRoot,
            'sites.publish_path' => $this->publishRoot,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->vaultRoot);
        File::deleteDirectory($this->publishRoot);
        Mockery::close();
        parent::tearDown();
    }

    public function test_content_edit_requires_active_subscription(): void
    {
        $owner = User::factory()->create();
        $website = $this->readyWebsite($owner);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', '<html><body><span data-field="name">Old</span></body></html>');

        $this->actingAs($owner)
            ->get(route('websites.content.edit', $website))
            ->assertRedirect(route('websites.subscription.show', $website));

        $this->actingAs($owner)->post(route('websites.content.update', $website), [
            'offering_type' => 'products',
            'offerings' => [['name' => 'New', 'description' => '', 'price' => 'R10']],
        ])->assertRedirect(route('websites.subscription.show', $website));

        File::deleteDirectory($website->sitePath());
    }

    public function test_stub_subscription_unlocks_content_editing_and_records_vault_snapshot(): void
    {
        $owner = User::factory()->create();
        $website = $this->readyWebsite($owner);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html',
            '<html><body><ul><li data-offering="1"><span data-field="name">Old</span>'
            .'<span data-field="description"></span><span data-field="price">R1</span></li></ul></body></html>');

        $this->actingAs($owner)
            ->post(route('websites.subscription.purchase', $website))
            ->assertRedirect(route('websites.subscription.show', $website));

        $this->assertTrue($website->fresh()->hasActiveEditingSubscription());

        $this->actingAs($owner)->post(route('websites.content.update', $website), [
            'offering_type' => 'products',
            'offerings' => [['name' => 'New product', 'description' => 'Nice', 'price' => 'R500']],
        ])->assertRedirect(route('websites.show', $website));

        $vault = WebsiteContentVault::forWebsite($website);
        $this->assertCount(1, $vault->listProductSnapshots());

        File::deleteDirectory($website->sitePath());
        File::deleteDirectory($this->vaultRoot.'/'.$website->id);
    }

    public function test_newsletter_generation_and_send_use_vault_and_mail(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['ai_credits' => 5]);
        $website = $this->readyWebsite($owner);

        $this->mock(NewsletterGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn([
                'subject' => 'Hello subscribers',
                'html' => '<p>News</p>',
                'text' => 'News',
            ]);
        });

        NewsletterSubscriber::create([
            'website_id' => $website->id,
            'email' => 'fan@example.com',
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'subscribed_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('websites.newsletters.store', $website), [
            'topic' => 'Weekly update',
        ])->assertRedirect(route('websites.newsletters.index', $website));

        $this->assertSame(3, $owner->fresh()->ai_credits);

        $vault = WebsiteContentVault::forWebsite($website);
        $newsletters = $vault->listNewsletters();
        $this->assertCount(1, $newsletters);

        $uuid = $newsletters[0]['uuid'];

        $this->actingAs($owner)->post(route('websites.newsletters.send', [$website, $uuid]))
            ->assertRedirect(route('websites.newsletters.show', [$website, $uuid]));

        Mail::assertSent(WebsiteNewsletter::class, 1);
        $this->assertSame('sent', $vault->findNewsletter($uuid)['status']);

        File::deleteDirectory($this->vaultRoot.'/'.$website->id);
    }

    public function test_newsletter_generation_refunds_credits_on_failure(): void
    {
        $owner = User::factory()->create(['ai_credits' => 3]);
        $website = $this->readyWebsite($owner);

        $this->mock(NewsletterGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('AI failed'));
        });

        $job = new GenerateNewsletterJob($website, 'Topic', null, 2);

        try {
            $job->handle(app(NewsletterGenerator::class));
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(5, $owner->fresh()->ai_credits);
    }

    public function test_poster_generation_writes_vault_with_mocked_exporter(): void
    {
        $owner = User::factory()->create(['ai_credits' => 5]);
        $website = $this->readyWebsite($owner);

        $this->mock(PosterGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn('<div>Poster HTML</div>');
        });

        $this->mock(PosterExporter::class, function ($mock) {
            $mock->shouldReceive('export')->once()->andReturnUsing(function ($html, $targetPath) {
                File::put($targetPath, 'png-bytes');

                return $targetPath;
            });
        });

        $this->actingAs($owner)->post(route('websites.posters.store', $website), [
            'brief' => 'Grand opening',
            'format' => 'instagram_square',
        ])->assertRedirect(route('websites.posters.index', $website));

        $this->assertSame(2, $owner->fresh()->ai_credits);

        $vault = WebsiteContentVault::forWebsite($website);
        $posters = $vault->listPosters();
        $this->assertCount(1, $posters);
        $this->assertSame('ready', $posters[0]['status']);

        File::deleteDirectory($this->vaultRoot.'/'.$website->id);
    }

    public function test_poster_job_refunds_credits_on_generator_failure(): void
    {
        $owner = User::factory()->create(['ai_credits' => 2]);
        $website = $this->readyWebsite($owner);

        $this->mock(PosterGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('AI failed'));
        });

        $job = new GeneratePosterJob($website, 'Brief', 'instagram_square', 3);

        try {
            $job->handle(app(PosterGenerator::class), app(PosterExporter::class));
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(5, $owner->fresh()->ai_credits);
    }

    public function test_public_newsletter_subscribe_and_unsubscribe(): void
    {
        $owner = User::factory()->create();
        $website = $this->readyWebsite($owner, Website::STATUS_PUBLISHED);

        $this->post(route('public.newsletter.subscribe', $website->slug), [
            'email' => 'reader@example.com',
            'name' => 'Reader',
        ])->assertRedirect();

        $subscriber = NewsletterSubscriber::first();
        $this->assertSame('reader@example.com', $subscriber->email);

        $this->get(route('public.newsletter.unsubscribe', [$website->slug, $subscriber->unsubscribe_token]))
            ->assertOk()
            ->assertSee('Unsubscribed');

        $this->assertSame(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $subscriber->fresh()->status);
    }

    public function test_publish_creates_custom_domain_symlink(): void
    {
        $owner = User::factory()->create();
        $website = $this->readyWebsite($owner);
        $website->update([
            'status' => Website::STATUS_PUBLISHED,
            'custom_domain' => 'www.myshop.test',
            'published_at' => now(),
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', '<h1>Live</h1>');

        app(PublishedSiteHost::class)->publish($website);

        $linkPath = $this->publishRoot.'/domains/www.myshop.test';
        $this->assertTrue(is_link($linkPath) || File::isDirectory($linkPath));

        File::deleteDirectory($website->sitePath());
        File::deleteDirectory($this->publishRoot);
    }

    private function readyWebsite(User $owner, string $status = Website::STATUS_READY): Website
    {
        return $owner->websites()->create([
            'name' => 'Marketing Shop',
            'slug' => 'marketing-shop-'.uniqid(),
            'status' => $status,
            'settings' => ['offering_type' => 'products', 'offerings' => []],
        ]);
    }
}
