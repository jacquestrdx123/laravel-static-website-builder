<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteSubscription;
use App\Services\PublishedSiteHost;
use App\Support\CreditsPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LockedCreditBillingTest extends TestCase
{
    use RefreshDatabase;

    private string $publishRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publishRoot = storage_path('framework/testing/locked-billing-published');
        config(['sites.publish_path' => $this->publishRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publishRoot);
        parent::tearDown();
    }

    public function test_publish_charges_hosting_credits_once_per_period(): void
    {
        $pricing = app(CreditsPricing::class);
        $owner = User::factory()->create(['ai_credits' => $pricing->websiteHostingCreditsPerMonth() + 1]);
        $website = $owner->websites()->create([
            'name' => 'Live Shop',
            'slug' => 'live-shop-'.uniqid(),
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', '<h1>Hi</h1>');

        $this->actingAs($owner)
            ->post(route('websites.publish', $website))
            ->assertRedirect(route('websites.show', $website));

        $this->assertSame(1, $owner->fresh()->ai_credits);
        $this->assertTrue($website->fresh()->hasActiveHostingSubscription());
        $this->assertSame(Website::STATUS_PUBLISHED, $website->fresh()->status);

        // Second publish within the hosting window does not charge again.
        $website->update(['status' => Website::STATUS_READY, 'published_at' => null]);
        app(PublishedSiteHost::class)->unpublish($website->fresh());

        $this->actingAs($owner)
            ->post(route('websites.publish', $website))
            ->assertRedirect(route('websites.show', $website));

        $this->assertSame(1, $owner->fresh()->ai_credits);

        File::deleteDirectory($website->sitePath());
    }

    public function test_editing_subscription_purchase_charges_yearly_credits(): void
    {
        $pricing = app(CreditsPricing::class);
        $owner = User::factory()->create(['ai_credits' => $pricing->editingCreditsPerYear()]);
        $website = $owner->websites()->create([
            'name' => 'Edit Shop',
            'slug' => 'edit-shop-'.uniqid(),
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        $this->actingAs($owner)
            ->post(route('websites.subscription.purchase', $website))
            ->assertRedirect(route('websites.subscription.show', $website));

        $this->assertSame(0, $owner->fresh()->ai_credits);
        $this->assertTrue($website->fresh()->hasActiveEditingSubscription());
        $this->assertSame(
            WebsiteSubscription::TYPE_MANUAL_EDITING,
            $website->subscriptions()->first()->type
        );
    }
}
