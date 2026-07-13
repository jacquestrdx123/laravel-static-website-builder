<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Website;
use App\Services\WebsiteContentVault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WebsiteContentVaultTest extends TestCase
{
    use RefreshDatabase;

    private string $vaultRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vaultRoot = storage_path('framework/testing/website-data');
        config(['sites.website_data_path' => $this->vaultRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->vaultRoot);
        parent::tearDown();
    }

    public function test_vault_creates_sqlite_and_writes_product_snapshot(): void
    {
        $website = $this->makeWebsite();
        $vault = WebsiteContentVault::forWebsite($website);

        $uuid = $vault->recordProductSnapshot('content_edit', ['before' => true], ['after' => true]);

        $this->assertFileExists($this->vaultRoot.'/'.$website->id.'/vault.sqlite');
        $this->assertFileExists($this->vaultRoot.'/'.$website->id.'/products/'.$uuid.'/before.json');
        $this->assertFileExists($this->vaultRoot.'/'.$website->id.'/products/'.$uuid.'/snapshot.xml');

        $snapshots = $vault->listProductSnapshots();
        $this->assertCount(1, $snapshots);
        $this->assertSame($uuid, $snapshots[0]['uuid']);
    }

    public function test_vault_records_newsletter_and_marks_sent(): void
    {
        $website = $this->makeWebsite();
        $vault = WebsiteContentVault::forWebsite($website);

        $uuid = $vault->recordNewsletter(
            'Summer sale',
            ['topic' => 'Summer sale'],
            ['subject' => 'Big savings'],
            '<p>Hello</p>',
            'Hello',
        );

        $newsletter = $vault->findNewsletter($uuid);
        $this->assertNotNull($newsletter);
        $this->assertSame('ready', $newsletter['status']);
        $this->assertSame('<p>Hello</p>', $vault->newsletterHtml($uuid));

        $vault->markNewsletterSent($uuid, 3);

        $sent = $vault->findNewsletter($uuid);
        $this->assertSame('sent', $sent['status']);
        $this->assertSame(3, (int) $sent['recipient_count']);
        $this->assertFileExists($this->vaultRoot.'/'.$website->id.'/newsletters/'.$uuid.'/newsletter.xml');
    }

    public function test_vault_records_poster_and_updates_png(): void
    {
        $website = $this->makeWebsite();
        $vault = WebsiteContentVault::forWebsite($website);

        $uuid = $vault->recordPoster(
            ['brief' => 'Launch'],
            'instagram_square',
            '<div>Poster</div>',
            null,
        );

        $poster = $vault->findPoster($uuid);
        $this->assertNotNull($poster);
        $this->assertSame('html_only', $poster['status']);

        $pngRelative = $poster['dir_path'].'/poster.png';
        File::put($this->vaultRoot.'/'.$website->id.'/'.$pngRelative, 'fake-png');

        $vault->updatePosterPng($uuid, $pngRelative);

        $updated = $vault->findPoster($uuid);
        $this->assertSame('ready', $updated['status']);
        $this->assertNotNull($vault->posterPngPath($uuid));
        $this->assertFileExists($this->vaultRoot.'/'.$website->id.'/'.$poster['dir_path'].'/poster.xml');
    }

    private function makeWebsite(): Website
    {
        $user = User::factory()->create();

        return $user->websites()->create([
            'name' => 'Vault Shop',
            'slug' => 'vault-shop',
            'status' => Website::STATUS_READY,
            'settings' => ['offering_type' => 'products'],
        ]);
    }
}
