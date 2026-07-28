<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Billing;
use App\Livewire\Domains\Index as DomainIndex;
use App\Livewire\Pricing;
use App\Livewire\Websites\Newsletters\Index as NewsletterIndex;
use App\Livewire\Websites\Posters\Index as PosterIndex;
use App\Livewire\Websites\Subscribers;
use App\Livewire\Websites\Subscription;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireCustomerSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_and_billing_pages_are_livewire(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class);
        $this->get(route('register'))->assertOk()->assertSeeLivewire(Register::class);

        $user = User::factory()->create(['ai_credits' => 1]);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeLivewire(Billing::class);

        $this->actingAs($user)
            ->get(route('pricing'))
            ->assertOk()
            ->assertSeeLivewire(Pricing::class);

        $this->actingAs($user)
            ->get(route('domains.index'))
            ->assertOk()
            ->assertSeeLivewire(DomainIndex::class);
    }

    public function test_website_marketing_pages_are_livewire(): void
    {
        $user = User::factory()->create();
        $website = $user->websites()->create([
            'name' => 'Bakery',
            'slug' => 'bakery',
            'status' => Website::STATUS_READY,
            'settings' => [],
        ]);

        $this->actingAs($user)
            ->get(route('websites.subscription.show', $website))
            ->assertOk()
            ->assertSeeLivewire(Subscription::class);

        $this->actingAs($user)
            ->get(route('websites.subscribers.index', $website))
            ->assertOk()
            ->assertSeeLivewire(Subscribers::class);

        $this->actingAs($user)
            ->get(route('websites.newsletters.index', $website))
            ->assertOk()
            ->assertSeeLivewire(NewsletterIndex::class);

        $this->actingAs($user)
            ->get(route('websites.posters.index', $website))
            ->assertOk()
            ->assertSeeLivewire(PosterIndex::class);
    }

    public function test_livewire_billing_purchase_adds_credits(): void
    {
        $user = User::factory()->create(['ai_credits' => 1]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->call('purchase', 5)
            ->assertHasNoErrors();

        $this->assertSame(6, $user->fresh()->ai_credits);
    }

    public function test_livewire_register_grants_welcome_credit(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Ada')
            ->set('email', 'ada@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::firstWhere('email', 'ada@example.com')->ai_credits);
    }
}
