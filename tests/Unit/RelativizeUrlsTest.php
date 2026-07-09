<?php

namespace Tests\Unit;

use App\Services\WebsiteGenerator;
use Tests\TestCase;

class RelativizeUrlsTest extends TestCase
{
    private function generator(): WebsiteGenerator
    {
        return new WebsiteGenerator(new \Anthropic\Client(apiKey: 'test'));
    }

    public function test_root_absolute_references_become_relative_in_html(): void
    {
        $html = <<<'HTML'
        <link rel="stylesheet" href="/styles.css">
        <script src="/script.js"></script>
        <img src="/assets/image-1.jpg" srcset="/assets/image-1.jpg 1x, /assets/image-2.jpg 2x">
        <a href="/about.html">About</a>
        <video poster="/assets/poster.png"></video>
        <style>.hero { background: url('/assets/hero.webp'); }</style>
        HTML;

        $out = $this->generator()->relativizeUrls('index.html', $html);

        $this->assertStringContainsString('href="styles.css"', $out);
        $this->assertStringContainsString('src="script.js"', $out);
        $this->assertStringContainsString('src="assets/image-1.jpg"', $out);
        $this->assertStringContainsString('srcset="assets/image-1.jpg 1x, assets/image-2.jpg 2x"', $out);
        $this->assertStringContainsString('href="about.html"', $out);
        $this->assertStringContainsString('poster="assets/poster.png"', $out);
        $this->assertStringContainsString("url('assets/hero.webp')", $out);
    }

    public function test_documents_in_subdirectories_get_parent_prefixes(): void
    {
        $out = $this->generator()->relativizeUrls(
            'pages/about.html',
            '<link href="/styles.css"><img src="/assets/image-1.jpg">'
        );

        $this->assertStringContainsString('href="../styles.css"', $out);
        $this->assertStringContainsString('src="../assets/image-1.jpg"', $out);
    }

    public function test_external_relative_and_anchor_urls_are_untouched(): void
    {
        $html = '<a href="https://example.com/x">x</a>'
            .'<script src="//cdn.example.com/lib.js"></script>'
            .'<img src="assets/image-1.jpg">'
            .'<a href="#contact">contact</a>'
            .'<a href="mailto:hi@example.com">mail</a>';

        $this->assertSame($html, $this->generator()->relativizeUrls('index.html', $html));
    }

    public function test_css_files_are_rewritten(): void
    {
        $out = $this->generator()->relativizeUrls(
            'styles.css',
            '.hero { background-image: url("/assets/hero.jpg"); } .ok { background: url(assets/b.png); }'
        );

        $this->assertStringContainsString('url("assets/hero.jpg")', $out);
        $this->assertStringContainsString('url(assets/b.png)', $out);
    }
}
