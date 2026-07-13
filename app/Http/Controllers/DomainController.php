<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDomainAccess;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\User;
use App\Models\Website;
use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class DomainController extends Controller
{
    use AuthorizesDomainAccess;

    public function index(Request $request): View
    {
        return view('domains.index', [
            'domains' => $request->user()->domains()->with('website')->get(),
        ]);
    }

    public function create(Request $request, HostAfricaClient $client, DomainCreditPricing $pricing): View|RedirectResponse
    {
        $domain = strtolower((string) $request->query('domain', ''));

        if (blank($domain)) {
            return redirect()->route('domains.search')
                ->with('error', 'Choose a domain from search results first.');
        }

        $creditCost = $this->quoteCredits($client, $pricing, 'register', $domain);

        return view('domains.register', [
            'domain' => $domain,
            'mode' => 'register',
            'creditCost' => $creditCost,
            'defaultContact' => DomainContactBuilder::fromUser($request->user()),
            'defaultNameservers' => DomainContactBuilder::defaultNameservers(),
            'websites' => $request->user()->websites()->latest()->get(),
        ]);
    }

    public function store(Request $request, HostAfricaClient $client, DomainCreditPricing $pricing): RedirectResponse
    {
        $data = $this->validatedOrderRequest($request);
        $contact = DomainContactBuilder::contactFromValidated($data['contact']);
        $nameservers = $this->validatedNameservers($data['nameservers']);
        $user = $request->user();

        try {
            $apiPricing = $client->pricing('register', $data['domain']);
            $price = $this->extractPrice($apiPricing, 'register');
            $credits = $pricing->creditsFor($apiPricing, 'register', (int) $data['regperiod'], $data['addons'] ?? []);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (! $this->chargeCredits($user, $credits, 'Domain registration: '.$data['domain'])) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$credits.' credits to register this domain. You have '.$user->ai_credits.'.');
        }

        try {
            $response = $client->register([
                'domain' => $data['domain'],
                'regperiod' => $data['regperiod'],
                'nameservers' => $nameservers,
                'contacts' => DomainContactBuilder::contactsPayload($contact),
                'addons' => [
                    'dnsmanagement' => (int) ($data['addons']['dnsmanagement'] ?? 0),
                    'emailforwarding' => (int) ($data['addons']['emailforwarding'] ?? 0),
                    'idprotection' => (int) ($data['addons']['idprotection'] ?? 0),
                ],
            ]);
        } catch (RuntimeException $e) {
            $user->addCredits($credits, 'Refund: domain registration failed ('.$data['domain'].')');

            return back()->withInput()->with('error', $e->getMessage());
        }

        $domainRecord = DB::transaction(function () use ($user, $data, $contact, $nameservers, $price, $credits, $response) {
            $domainRecord = Domain::create([
                'user_id' => $user->id,
                'website_id' => $data['website_id'] ?? null,
                'domain' => strtolower($data['domain']),
                'status' => Domain::STATUS_ACTIVE,
                'regperiod' => $data['regperiod'],
                'registered_at' => now(),
                'expires_at' => now()->addYears($data['regperiod']),
                'id_protection' => (bool) ($data['addons']['idprotection'] ?? false),
                'nameservers' => $nameservers,
                'contacts' => $contact,
                'meta' => ['register_response' => $response],
            ]);

            DomainOrder::create([
                'user_id' => $user->id,
                'domain_id' => $domainRecord->id,
                'type' => DomainOrder::TYPE_REGISTER,
                'domain' => $domainRecord->domain,
                'regperiod' => $data['regperiod'],
                'credits' => $credits,
                'price' => $price,
                'status' => DomainOrder::STATUS_COMPLETED,
                'note' => 'Paid with '.$credits.' credits',
            ]);

            if ($domainRecord->website_id) {
                Website::whereKey($domainRecord->website_id)
                    ->where('user_id', $user->id)
                    ->update(['custom_domain' => $domainRecord->domain]);
            }

            return $domainRecord;
        });

        return redirect()
            ->route('domains.show', $domainRecord)
            ->with('status', 'Domain registered successfully. '.$credits.' credits were deducted.');
    }

    public function show(Request $request, Domain $domain, HostAfricaClient $client, DomainCreditPricing $pricing): View
    {
        $this->authorizeDomain($request, $domain);

        $information = null;
        $lock = null;
        $renewCredits = null;

        try {
            $information = $client->information($domain->domain);
            $lock = $client->getLock($domain->domain);
            $renewCredits = $this->quoteCredits($client, $pricing, 'renew', $domain->domain);
        } catch (RuntimeException) {
            // Keep the page usable when the upstream API is unavailable.
        }

        return view('domains.show', [
            'domain' => $domain->load('website'),
            'information' => $information,
            'lock' => $lock,
            'renewCredits' => $renewCredits,
            'websites' => $request->user()->websites()->latest()->get(),
        ]);
    }

    public function renew(Request $request, Domain $domain, HostAfricaClient $client, DomainCreditPricing $pricing): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'regperiod' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user = $request->user();

        try {
            $apiPricing = $client->pricing('renew', $domain->domain);
            $price = $this->extractPrice($apiPricing, 'renew');
            $credits = $pricing->creditsFor($apiPricing, 'renew', (int) $data['regperiod']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $this->chargeCredits($user, $credits, 'Domain renewal: '.$domain->domain)) {
            return redirect()->route('billing.index')
                ->with('error', 'You need '.$credits.' credits to renew this domain. You have '.$user->ai_credits.'.');
        }

        try {
            $response = $client->renew([
                'domain' => $domain->domain,
                'regperiod' => $data['regperiod'],
                'addons' => [
                    'dnsmanagement' => 0,
                    'emailforwarding' => 0,
                    'idprotection' => $domain->id_protection ? 1 : 0,
                ],
            ]);
        } catch (RuntimeException $e) {
            $user->addCredits($credits, 'Refund: domain renewal failed ('.$domain->domain.')');

            return back()->with('error', $e->getMessage());
        }

        $domain->update([
            'regperiod' => $data['regperiod'],
            'expires_at' => ($domain->expires_at ?? now())->addYears($data['regperiod']),
            'meta' => array_merge($domain->meta ?? [], ['renew_response' => $response]),
        ]);

        DomainOrder::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'type' => DomainOrder::TYPE_RENEW,
            'domain' => $domain->domain,
            'regperiod' => $data['regperiod'],
            'credits' => $credits,
            'price' => $price,
            'status' => DomainOrder::STATUS_COMPLETED,
            'note' => 'Paid with '.$credits.' credits',
        ]);

        return back()->with('status', 'Domain renewed successfully. '.$credits.' credits were deducted.');
    }

    public function link(Request $request, Domain $domain): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'website_id' => ['required', 'exists:websites,id'],
        ]);

        $website = Website::whereKey($data['website_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        Website::where('user_id', $request->user()->id)
            ->where('custom_domain', $domain->domain)
            ->whereKeyNot($website->id)
            ->update(['custom_domain' => null]);

        $domain->update(['website_id' => $website->id]);
        $website->update(['custom_domain' => $domain->domain]);

        return back()->with('status', 'Domain linked to '.$website->name.'.');
    }

    public function unlink(Request $request, Domain $domain): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        if ($domain->website_id) {
            Website::whereKey($domain->website_id)
                ->where('user_id', $request->user()->id)
                ->update(['custom_domain' => null]);
        }

        $domain->update(['website_id' => null]);

        return back()->with('status', 'Domain unlinked from website.');
    }

    public function sync(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        try {
            $response = $client->sync($domain->domain);
            $information = $client->information($domain->domain);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update([
            'meta' => array_merge($domain->meta ?? [], [
                'sync_response' => $response,
                'information' => $information,
            ]),
            'expires_at' => isset($information['expirydate'])
                ? now()->parse($information['expirydate'])
                : $domain->expires_at,
            'status' => Domain::STATUS_ACTIVE,
        ]);

        return back()->with('status', 'Domain synced with registrar.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedOrderRequest(Request $request): array
    {
        return $request->validate([
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i'],
            'regperiod' => ['required', 'integer', 'min:1', 'max:10'],
            'website_id' => ['nullable', 'exists:websites,id'],
            'nameservers' => ['required', 'array'],
            'nameservers.ns1' => ['required', 'string', 'max:255'],
            'nameservers.ns2' => ['required', 'string', 'max:255'],
            'nameservers.ns3' => ['nullable', 'string', 'max:255'],
            'nameservers.ns4' => ['nullable', 'string', 'max:255'],
            'nameservers.ns5' => ['nullable', 'string', 'max:255'],
            'addons' => ['nullable', 'array'],
            'addons.dnsmanagement' => ['nullable', 'boolean'],
            'addons.emailforwarding' => ['nullable', 'boolean'],
            'addons.idprotection' => ['nullable', 'boolean'],
            'contact' => ['required', 'array'],
            'contact.firstname' => ['required', 'string', 'max:100'],
            'contact.lastname' => ['required', 'string', 'max:100'],
            'contact.companyname' => ['nullable', 'string', 'max:150'],
            'contact.email' => ['required', 'email', 'max:255'],
            'contact.address1' => ['required', 'string', 'max:255'],
            'contact.address2' => ['nullable', 'string', 'max:255'],
            'contact.city' => ['required', 'string', 'max:100'],
            'contact.state' => ['required', 'string', 'max:100'],
            'contact.postcode' => ['required', 'string', 'max:20'],
            'contact.country' => ['required', 'string', 'size:2'],
            'contact.phonenumber' => ['required', 'string', 'max:30'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $nameservers
     * @return array<string, string>
     */
    private function validatedNameservers(array $nameservers): array
    {
        return array_filter([
            'ns1' => $nameservers['ns1'] ?? null,
            'ns2' => $nameservers['ns2'] ?? null,
            'ns3' => $nameservers['ns3'] ?? null,
            'ns4' => $nameservers['ns4'] ?? null,
            'ns5' => $nameservers['ns5'] ?? null,
        ]);
    }

    private function extractPrice(array $pricing, string $type): ?string
    {
        if (isset($pricing[$type])) {
            return is_array($pricing[$type])
                ? (string) ($pricing[$type]['price'] ?? reset($pricing[$type]))
                : (string) $pricing[$type];
        }

        if (isset($pricing['price'])) {
            return (string) $pricing['price'];
        }

        return null;
    }

    private function chargeCredits(User $user, int $credits, string $description): bool
    {
        try {
            $user->spendCredits($credits, $description);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private function quoteCredits(
        HostAfricaClient $client,
        DomainCreditPricing $pricing,
        string $type,
        string $domain,
        int $regperiod = 1,
        array $addons = [],
    ): int {
        try {
            $apiPricing = $client->pricing($type, $domain);

            return $pricing->creditsFor($apiPricing, $type, $regperiod, $addons);
        } catch (RuntimeException) {
            return $pricing->creditsFromPriceString(null) * $regperiod;
        }
    }
}
