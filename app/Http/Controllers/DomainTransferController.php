<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Website;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class DomainTransferController extends Controller
{
    public function create(Request $request): View
    {
        $defaultContact = DomainContactBuilder::fromUser($request->user());

        return view('domains.register', [
            'domain' => strtolower((string) $request->query('domain', '')),
            'mode' => 'transfer',
            'defaultContact' => $defaultContact,
            'defaultNameservers' => DomainContactBuilder::defaultNameservers(),
            'websites' => $request->user()->websites()->latest()->get(),
        ]);
    }

    public function store(Request $request, HostAfricaClient $client): RedirectResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i'],
            'eppcode' => ['required', 'string', 'max:255'],
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

        $contact = DomainContactBuilder::contactFromValidated($data['contact']);
        $nameservers = array_filter([
            'ns1' => $data['nameservers']['ns1'],
            'ns2' => $data['nameservers']['ns2'],
            'ns3' => $data['nameservers']['ns3'] ?? null,
            'ns4' => $data['nameservers']['ns4'] ?? null,
            'ns5' => $data['nameservers']['ns5'] ?? null,
        ]);

        try {
            $pricing = $client->pricing('transfer', $data['domain']);
            $price = isset($pricing['transfer'])
                ? (string) (is_array($pricing['transfer']) ? reset($pricing['transfer']) : $pricing['transfer'])
                : ((string) ($pricing['price'] ?? null));

            $response = $client->transfer([
                'domain' => strtolower($data['domain']),
                'eppcode' => $data['eppcode'],
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
            return back()->withInput()->with('error', $e->getMessage());
        }

        $domainRecord = DB::transaction(function () use ($request, $data, $contact, $nameservers, $price, $response) {
            $domainRecord = Domain::create([
                'user_id' => $request->user()->id,
                'website_id' => $data['website_id'] ?? null,
                'domain' => strtolower($data['domain']),
                'status' => Domain::STATUS_PENDING,
                'regperiod' => $data['regperiod'],
                'nameservers' => $nameservers,
                'contacts' => $contact,
                'meta' => ['transfer_response' => $response],
            ]);

            DomainOrder::create([
                'user_id' => $request->user()->id,
                'domain_id' => $domainRecord->id,
                'type' => DomainOrder::TYPE_TRANSFER,
                'domain' => $domainRecord->domain,
                'regperiod' => $data['regperiod'],
                'price' => $price,
                'status' => DomainOrder::STATUS_COMPLETED,
                'note' => '[stub - no payment taken]',
            ]);

            if ($domainRecord->website_id) {
                Website::whereKey($domainRecord->website_id)
                    ->where('user_id', $request->user()->id)
                    ->update(['custom_domain' => $domainRecord->domain]);
            }

            return $domainRecord;
        });

        return redirect()
            ->route('domains.show', $domainRecord)
            ->with('status', 'Domain transfer initiated.');
    }
}
