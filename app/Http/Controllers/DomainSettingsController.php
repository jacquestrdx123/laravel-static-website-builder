<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDomainAccess;
use App\Models\Domain;
use App\Services\HostAfricaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DomainSettingsController extends Controller
{
    use AuthorizesDomainAccess;

    public function edit(Request $request, Domain $domain, HostAfricaClient $client): View
    {
        $this->authorizeDomain($request, $domain);

        $lock = null;
        $emailForwarding = null;

        try {
            $lock = $client->getLock($domain->domain);
            $emailForwarding = $client->getEmailForwarding($domain->domain);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        return view('domains.settings', [
            'domain' => $domain,
            'lock' => $lock,
            'emailForwarding' => $emailForwarding,
        ]);
    }

    public function updateLock(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'lockstatus' => ['required', 'in:locked,unlocked'],
        ]);

        try {
            $client->saveLock($domain->domain, $data['lockstatus']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update(['registrar_locked' => $data['lockstatus'] === 'locked']);

        return back()->with('status', 'Registrar lock updated.');
    }

    public function updateIdProtection(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        try {
            $client->protectId($domain->domain, $data['status'] ? 1 : 0);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update(['id_protection' => $data['status']]);

        return back()->with('status', 'ID protection updated.');
    }

    public function updateEmailForwarding(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'prefix' => ['required', 'array', 'min:1'],
            'prefix.*' => ['required', 'string', 'max:100'],
            'forwardto' => ['required', 'array', 'min:1'],
            'forwardto.*' => ['required', 'email', 'max:255'],
        ]);

        if (count($data['prefix']) !== count($data['forwardto'])) {
            return back()->with('error', 'Each email prefix needs a matching forward address.');
        }

        try {
            $client->saveEmailForwarding($domain->domain, $data['prefix'], $data['forwardto']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Email forwarding updated.');
    }

    public function eppCode(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        try {
            $response = $client->eppCode($domain->domain);
            $code = $response['eppcode'] ?? $response['code'] ?? $response['raw'] ?? json_encode($response);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'EPP code: '.$code);
    }

    public function release(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        $data = $request->validate([
            'transfertag' => ['required', 'string', 'max:255'],
        ]);

        try {
            $client->release($domain->domain, $data['transfertag']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update(['status' => Domain::STATUS_TRANSFERRED]);

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domain release requested.');
    }

    public function requestDeletion(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        try {
            $client->requestDeletion($domain->domain);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update(['status' => Domain::STATUS_CANCELLED]);

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domain deletion requested.');
    }

    public function transferSync(Request $request, Domain $domain, HostAfricaClient $client): RedirectResponse
    {
        $this->authorizeDomain($request, $domain);

        try {
            $response = $client->transferSync($domain->domain);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $domain->update([
            'status' => Domain::STATUS_ACTIVE,
            'meta' => array_merge($domain->meta ?? [], ['transfer_sync_response' => $response]),
        ]);

        return back()->with('status', 'Transfer sync completed.');
    }
}
