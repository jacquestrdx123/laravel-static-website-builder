<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Website;
use App\Services\SiteContentUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteContentUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_HTML = <<<'HTML'
    <!DOCTYPE html>
    <html lang="en">
    <head><title>Café Test</title></head>
    <body>
    <header><p data-content="tagline">Old tagline</p></header>
    <section aria-labelledby="menu">
        <ul class="menu">
            <li data-offering="1">
                <h3 data-field="name">Flat white</h3>
                <p data-field="description">Silky espresso and milk</p>
                <span data-field="price">R38</span>
            </li>
            <li data-offering="2">
                <h3 data-field="name">Croissant</h3>
                <p data-field="description">Buttery, baked daily</p>
                <span data-field="price">R32</span>
            </li>
        </ul>
    </section>
    <footer><a data-content="contact-email" href="mailto:old@cafe.test">old@cafe.test</a></footer>
    </body>
    </html>
    HTML;

    private function makeWebsite(array $settings): Website
    {
        $user = User::factory()->create();

        $website = $user->websites()->create([
            'name' => 'Café Test',
            'slug' => 'cafe-test',
            'status' => Website::STATUS_READY,
            'settings' => $settings,
        ]);

        File::ensureDirectoryExists($website->sitePath());
        File::put($website->sitePath().'/index.html', self::SAMPLE_HTML);

        return $website;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/sites/cafe-test'));
        parent::tearDown();
    }

    public function test_offerings_are_rebuilt_from_settings(): void
    {
        $website = $this->makeWebsite([
            'offerings' => [
                ['name' => 'Flat white', 'description' => 'Silky espresso and milk', 'price' => 'R42'],
                ['name' => 'Croissant', 'description' => 'Buttery, baked daily', 'price' => 'R32'],
                ['name' => 'Pain au chocolat', 'description' => 'New this week', 'price' => 'R36'],
            ],
        ]);

        $changed = app(SiteContentUpdater::class)->apply($website);
        $this->assertSame(1, $changed);

        $html = File::get($website->sitePath().'/index.html');

        // Price updated, new item added with the same structure, order preserved.
        $this->assertStringContainsString('R42', $html);
        $this->assertStringContainsString('Pain au chocolat', $html);
        $this->assertStringContainsString('New this week', $html);
        $this->assertSame(3, substr_count($html, 'data-offering'));
        $this->assertStringNotContainsString('R38', $html);
    }

    public function test_removing_items_shrinks_the_list(): void
    {
        $website = $this->makeWebsite([
            'offerings' => [
                ['name' => 'Flat white', 'description' => '', 'price' => 'R42'],
            ],
        ]);

        app(SiteContentUpdater::class)->apply($website);

        $html = File::get($website->sitePath().'/index.html');
        $this->assertSame(1, substr_count($html, 'data-offering'));
        $this->assertStringNotContainsString('Croissant', $html);
    }

    public function test_tagline_and_contact_email_update_including_mailto(): void
    {
        $website = $this->makeWebsite([
            'tagline' => 'Fresh every morning',
            'contact_email' => 'hello@cafe.test',
            'offerings' => [],
        ]);

        app(SiteContentUpdater::class)->apply($website);

        $html = File::get($website->sitePath().'/index.html');
        $this->assertStringContainsString('Fresh every morning', $html);
        $this->assertStringContainsString('mailto:hello@cafe.test', $html);
        $this->assertStringContainsString('>hello@cafe.test<', $html);
        // Empty offerings list leaves the menu untouched.
        $this->assertStringContainsString('Flat white', $html);
    }

    public function test_values_are_escaped_as_text_not_markup(): void
    {
        $website = $this->makeWebsite([
            'offerings' => [
                ['name' => 'Fish & chips <script>alert(1)</script>', 'description' => '', 'price' => 'R99'],
            ],
        ]);

        app(SiteContentUpdater::class)->apply($website);

        $html = File::get($website->sitePath().'/index.html');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('Fish &amp; chips', $html);
    }

    public function test_read_offerings_from_site_returns_live_copy(): void
    {
        $website = $this->makeWebsite([
            'offerings' => [
                ['name' => 'Flat white', 'description' => 'Short note', 'price' => 'R38'],
            ],
        ]);

        $live = app(SiteContentUpdater::class)->readOfferingsFromSite($website);

        $this->assertCount(2, $live);
        $this->assertSame('Flat white', $live[0]['name']);
        $this->assertSame('Silky espresso and milk', $live[0]['description']);
        $this->assertSame('R38', $live[0]['price']);
    }
}
