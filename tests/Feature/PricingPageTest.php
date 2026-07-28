<?php

namespace Tests\Feature;

use App\Livewire\Pricing;
use App\Models\User;
use App\Support\CreditsPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_is_public_for_the_marketing_funnel(): void
    {
        // The homepage links logged-out visitors to pricing - it must not
        // bounce them to the login page.
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSeeLivewire(Pricing::class);
    }

    public function test_authenticated_user_sees_locked_pricing_catalog(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('pricing'));

        $response->assertOk();
        $response->assertSeeLivewire(Pricing::class);
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

        $catalog = app(CreditsPricing::class)->catalog();
        $this->assertSame(50.0, $catalog['credit_value_zar']);
        $this->assertCount(5, $catalog['items']);
        $this->assertSame('website_generation', $catalog['items'][0]['key']);
        $this->assertSame('newsletter', $catalog['items'][3]['key']);
        $this->assertSame('marketing_poster', $catalog['items'][4]['key']);

        Livewire::actingAs($user)
            ->test(Pricing::class)
            ->assertSee('Locked-in pricing');
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
