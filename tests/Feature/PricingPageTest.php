<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_requires_authentication(): void
    {
        $this->get(route('pricing'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_locked_pricing_catalog(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('pricing'));

        $response->assertOk();
        $response->assertSee('Locked-in pricing');
        $response->assertSee('1 credit = R50', false);
        $response->assertSee('Website Generation');
        $response->assertSee('Website Hosting');
        $response->assertSee('Editing without AI');
        $response->assertSee('Newsletter functionality');
        $response->assertSee('Marketing poster');
        $response->assertSee('Professional email service with advanced reporting');
        $response->assertSee('See who opened your newsletter');
        $response->assertSee('See who clicked a link');
        $response->assertViewHas('pricing', function (array $pricing) {
            return $pricing['credit_value_zar'] === 50.0
                && count($pricing['items']) === 5
                && $pricing['items'][0]['key'] === 'website_generation'
                && $pricing['items'][3]['key'] === 'newsletter'
                && $pricing['items'][4]['key'] === 'marketing_poster';
        });
    }

    public function test_main_nav_includes_pricing_and_product_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pricing')
            ->assertSee('My Websites')
            ->assertSee('My Domains')
            ->assertSee('My Posters')
            ->assertSee('My Newsletters')
            ->assertSee(route('pricing'), false)
            ->assertSee(route('websites.index'), false)
            ->assertSee(url('/posters'), false)
            ->assertSee(url('/newsletters'), false);
    }
}
