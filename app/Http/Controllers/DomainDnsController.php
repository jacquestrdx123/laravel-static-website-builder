<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDomainAccess;
use App\Models\Domain;
use App\Services\HostAfricaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DomainDnsController extends Controller
{
    use AuthorizesDomainAccess;

    public function edit(Request $request, Domain $domain, HostAfricaClient $client): View
    {
        $this->authorizeDomain($request, $domain);

        $records = [];

        try {
            $response = $client->getDns($domain->domain);
            $records = $response['dnsrecords'] ?? $response['records'] ?? $response;
            if (! is_array($records)) {
                $records = [];
            }
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        return view('domains.dns', [
            'domain' => $domain,
            'records' => $records,
        ]);
    }

    public function update(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.hostname' => ['required', 'string', 'max:255'],
            'records.*.type' => ['required', 'string', 'max:10'],
            'records.*.address' => ['required', 'string', 'max:255'],
            'records.*.priority' => ['nullable', 'integer', 'min:0'],
            'records.*.recid' => ['nullable', 'string', 'max:50'],
        ]);

        $records = collect($data['records'])
            ->map(fn (array $record) => array_filter([
                'hostname' => $record['hostname'],
                'type' => strtoupper($record['type']),
                'address' => $record['address'],
                'priority' => $record['priority'] ?? 0,
                'recid' => $record['recid'] ?? null,
            ]))
            ->values()
            ->all();

        try {
            $client->saveDns($domain->domain, $records);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('domains.dns.edit', $domain)
            ->with('status', 'DNS records updated.');
    }
}
