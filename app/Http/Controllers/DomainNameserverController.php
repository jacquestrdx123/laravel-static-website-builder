<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDomainAccess;
use App\Models\Domain;
use App\Services\HostAfricaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DomainNameserverController extends Controller
{
    use AuthorizesDomainAccess;

    public function edit(Request $request, Domain $domain, HostAfricaClient $client): View
    {
        $this->authorizeDomain($request, $domain);

        $nameservers = $domain->nameservers ?? [];

        try {
            $response = $client->getNameservers($domain->domain);
            if (is_array($response)) {
                $nameservers = array_filter([
                    'ns1' => $response['ns1'] ?? null,
                    'ns2' => $response['ns2'] ?? null,
                    'ns3' => $response['ns3'] ?? null,
                    'ns4' => $response['ns4'] ?? null,
                    'ns5' => $response['ns5'] ?? null,
                ]);
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        return view('domains.nameservers', [
            'domain' => $domain,
            'nameservers' => $nameservers,
        ]);
    }

    public function update(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'ns1' => ['required', 'string', 'max:255'],
            'ns2' => ['required', 'string', 'max:255'],
            'ns3' => ['nullable', 'string', 'max:255'],
            'ns4' => ['nullable', 'string', 'max:255'],
            'ns5' => ['nullable', 'string', 'max:255'],
        ]);

        $nameservers = array_filter($data);

        try {
            $client->saveNameservers($domain->domain, $nameservers);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $domain->update(['nameservers' => $nameservers]);

        return redirect()
            ->route('domains.nameservers.edit', $domain)
            ->with('status', 'Nameservers updated.');
    }

    public function registerChild(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'nameserver' => ['required', 'string', 'max:255'],
            'ipaddress' => ['required', 'ip'],
        ]);

        try {
            $client->registerNs($domain->domain, $data['nameserver'], $data['ipaddress']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Child nameserver registered.');
    }

    public function modifyChild(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'nameserver' => ['required', 'string', 'max:255'],
            'currentipaddress' => ['required', 'ip'],
            'newipaddress' => ['required', 'ip'],
        ]);

        try {
            $client->modifyNs(
                $domain->domain,
                $data['nameserver'],
                $data['currentipaddress'],
                $data['newipaddress']
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Child nameserver updated.');
    }

    public function deleteChild(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'nameserver' => ['required', 'string', 'max:255'],
        ]);

        try {
            $client->deleteNs($domain->domain, $data['nameserver']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Child nameserver deleted.');
    }
}
