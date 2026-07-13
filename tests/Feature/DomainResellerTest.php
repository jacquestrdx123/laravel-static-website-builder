<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainResellerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.hostafrica.endpoint' => 'https://hostafrica.test/api/index.php',
            'services.hostafrica.username' => 'reseller@example.com',
            'services.hostafrica.api_key' => 'test-api-key',
            'services.hostafrica.default_nameservers' => [
                'ns1' => 'ns1.hostafrica.com',
                'ns2' => 'ns2.hostafrica.com',
            ],
        ]);
    }

    public function test_domain_search_maps_availability_results(): void
    {
        Http::fake([
            'hostafrica.test/*' => Http::sequence()
                ->push(['results' => [
                    ['domain' => 'myshop.co.za', 'available' => true],
                    ['domain' => 'myshop.com', 'available' => false],
                ]])
                ->push(['register' => 'R99.00']),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('domains.search.perform'), [
            'searchTerm' => 'myshop',
        ]);

        $response->assertRedirect(route('domains.search'));
        $response->assertSessionHas('domain_search_results');

        $results = session('domain_search_results');
        $this->assertSame('myshop.co.za', $results[0]['domain']);
        $this->assertTrue($results[0]['available']);
        $this->assertSame('R99.00', $results[0]['price']);
        $this->assertFalse($results[1]['available']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/domains/lookup')
                && is_array($request['tldsToInclude'])
                && $request['tldsToInclude'] !== [];
        });
    }

    public function test_domain_registration_creates_domain_and_stub_order(): void
    {
        Http::fake([
            'hostafrica.test/*' => Http::sequence()
                ->push(['register' => 'R99.00'])
                ->push(['result' => 'success']),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('domains.register.store'), [
            'domain' => 'myshop.co.za',
            'regperiod' => 1,
            'nameservers' => [
                'ns1' => 'ns1.hostafrica.com',
                'ns2' => 'ns2.hostafrica.com',
            ],
            'contact' => [
                'firstname' => 'Jacques',
                'lastname' => 'Tredoux',
                'companyname' => 'SiteForge',
                'email' => 'jacques@example.com',
                'address1' => '1 Main Road',
                'address2' => '',
                'city' => 'Cape Town',
                'state' => 'WC',
                'postcode' => '8001',
                'country' => 'ZA',
                'phonenumber' => '+27210000000',
            ],
        ]);

        $domain = Domain::first();
        $response->assertRedirect(route('domains.show', $domain));

        $this->assertSame('myshop.co.za', $domain->domain);
        $this->assertSame(Domain::STATUS_ACTIVE, $domain->status);
        $this->assertSame($user->id, $domain->user_id);

        $order = DomainOrder::first();
        $this->assertSame(DomainOrder::TYPE_REGISTER, $order->type);
        $this->assertSame(DomainOrder::STATUS_COMPLETED, $order->status);
        $this->assertSame('[stub - no payment taken]', $order->note);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/order/domains/register')
                && $request->hasHeader('username', 'reseller@example.com')
                && $request->hasHeader('token')
                && $request['domain'] === 'myshop.co.za';
        });
    }

    public function test_user_cannot_manage_someone_elses_domain(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $domain = Domain::create([
            'user_id' => $owner->id,
            'domain' => 'owned.co.za',
            'status' => Domain::STATUS_ACTIVE,
            'regperiod' => 1,
        ]);

        $this->actingAs($other)
            ->get(route('domains.show', $domain))
            ->assertForbidden();
    }

    public function test_dns_update_posts_records_with_auth_headers(): void
    {
        Http::fake([
            'hostafrica.test/*' => Http::response(['result' => 'success']),
        ]);

        $user = User::factory()->create();
        $domain = Domain::create([
            'user_id' => $user->id,
            'domain' => 'myshop.co.za',
            'status' => Domain::STATUS_ACTIVE,
            'regperiod' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('domains.dns.update', $domain), [
            'records' => [
                [
                    'hostname' => '@',
                    'type' => 'A',
                    'address' => '203.0.113.10',
                    'priority' => 0,
                    'recid' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('domains.dns.edit', $domain));

        Http::assertSent(function ($request) use ($domain) {
            return str_contains($request->url(), '/domains/'.rawurlencode($domain->domain).'/dns')
                && $request->hasHeader('username', 'reseller@example.com')
                && $request->hasHeader('token')
                && $request['dnsrecords'][0]['hostname'] === '@'
                && $request['dnsrecords'][0]['address'] === '203.0.113.10';
        });
    }
}
