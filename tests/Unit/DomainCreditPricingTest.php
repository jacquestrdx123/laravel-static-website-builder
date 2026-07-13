<?php

namespace Tests\Unit;

use App\Services\DomainCreditPricing;
use Tests\TestCase;

class DomainCreditPricingTest extends TestCase
{
    public function test_converts_registrar_price_to_credits(): void
    {
        config(['sites.credit_unit_cents' => 2000]);

        $pricing = new DomainCreditPricing;

        $this->assertSame(5, $pricing->creditsFromPriceString('R99.00'));
        $this->assertSame(10, $pricing->creditsFor(['register' => 'R199.00'], 'register', 1));
        $this->assertSame(10, $pricing->creditsFor(['register' => 'R99.00'], 'register', 2));
    }

    public function test_addons_add_extra_credits(): void
    {
        config([
            'sites.credit_unit_cents' => 2000,
            'sites.domain_addon_credits' => [
                'dnsmanagement' => 1,
                'emailforwarding' => 1,
                'idprotection' => 2,
            ],
        ]);

        $pricing = new DomainCreditPricing;

        $this->assertSame(8, $pricing->creditsFor(
            ['register' => 'R99.00'],
            'register',
            1,
            ['dnsmanagement' => 1, 'idprotection' => 1]
        ));
    }
}
