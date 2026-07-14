<?php

namespace Tests\Unit;

use App\Support\CreditsPricing;
use Tests\TestCase;

class CreditsPricingTest extends TestCase
{
    public function test_credit_value_is_locked_at_fifty_rand(): void
    {
        $pricing = new CreditsPricing;

        $this->assertSame(50.0, $pricing->creditValueZar());
        $this->assertSame(50, config('credits.credit_value_zar'));
    }

    public function test_product_credit_amounts_match_locked_table(): void
    {
        $this->assertSame(15, config('credits.website_generation.credits'));
        $this->assertSame(3, config('credits.website_hosting.credits_per_month'));
        $this->assertSame(1.5, config('credits.editing_without_ai.credits_per_month'));
        $this->assertSame(12, config('credits.editing_without_ai.credits_per_year'));
        $this->assertSame(6, config('credits.newsletter.hosting_credits_per_month'));
        $this->assertSame(500, config('credits.newsletter.included_emails_per_month'));
        $this->assertSame(2, config('credits.newsletter.extra_block_credits'));
        $this->assertSame(500, config('credits.newsletter.extra_block_size'));
        $this->assertSame(2, config('credits.marketing_poster.credits'));
        $this->assertSame(2, config('credits.marketing_poster.retries_included'));
    }

    public function test_zar_conversion(): void
    {
        $pricing = new CreditsPricing;

        $this->assertSame(50.0, $pricing->toZar(1));
        $this->assertSame(750.0, $pricing->toZar(15));
        $this->assertSame(75.0, $pricing->toZar(1.5));
        $this->assertSame('R50', $pricing->formatZar(1));
        $this->assertSame('R750', $pricing->formatZar(15));
        $this->assertSame('R75', $pricing->formatZar(1.5));
        $this->assertSame('1.5 credits', $pricing->formatCredits(1.5));
    }

    public function test_newsletter_messaging_mentions_professional_email_and_reporting(): void
    {
        $pricing = new CreditsPricing;
        $catalog = $pricing->catalog();
        $newsletter = collect($catalog['items'])->firstWhere('key', 'newsletter');

        $this->assertNotNull($newsletter);
        $this->assertStringContainsString('Professional email service', $newsletter['description']);
        $this->assertStringContainsString('opens', strtolower($newsletter['description']));
        $this->assertStringContainsString('link clicks', strtolower($newsletter['description']));

        $featureText = implode(' ', $newsletter['features']);
        $this->assertStringContainsString('opened', strtolower($featureText));
        $this->assertStringContainsString('clicked', strtolower($featureText));
        $this->assertStringContainsString('Professional email delivery', $featureText);
    }

    public function test_catalog_contains_five_products_in_order(): void
    {
        $keys = collect((new CreditsPricing)->catalog()['items'])->pluck('key')->all();

        $this->assertSame([
            'website_generation',
            'website_hosting',
            'editing_without_ai',
            'newsletter',
            'marketing_poster',
        ], $keys);
    }
}
