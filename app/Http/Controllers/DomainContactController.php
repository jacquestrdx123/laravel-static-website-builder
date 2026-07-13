<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDomainAccess;
use App\Models\Domain;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DomainContactController extends Controller
{
    use AuthorizesDomainAccess;

    public function edit(Request $request, Domain $domain, HostAfricaClient $client): View
    {
        $this->authorizeDomain($request, $domain);

        $contact = $domain->contacts ?? DomainContactBuilder::fromUser($request->user());

        try {
            $response = $client->getContacts($domain->domain);
            $contact = $response['Registrant']
                ?? $response['registrant']
                ?? $response['contactdetails']['Registrant']
                ?? $contact;
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        return view('domains.contacts', [
            'domain' => $domain,
            'contact' => $contact,
        ]);
    }

    public function update(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'companyname' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'phonenumber' => ['required', 'string', 'max:30'],
        ]);

        $contact = DomainContactBuilder::contactFromValidated($data);

        try {
            $client->saveContacts($domain->domain, DomainContactBuilder::contactDetailsPayload($contact));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $domain->update(['contacts' => $contact]);

        return redirect()
            ->route('domains.contacts.edit', $domain)
            ->with('status', 'Contact details updated.');
    }
}
