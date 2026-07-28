<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Show extends Component
{
    #[Locked]
    public int $domainId;

    public function mount(Domain $domain): void
    {
        abort_unless($domain->user_id === auth()->id(), 403);
        $this->domainId = $domain->id;
    }

    public function render(HostAfricaClient $client, DomainCreditPricing $pricing)
    {
        $domain = Domain::query()->with('website')->findOrFail($this->domainId);
        abort_unless($domain->user_id === auth()->id(), 403);

        $information = null;
        $lock = null;
        $renewCredits = null;

        try {
            $information = $client->information($domain->domain);
            $lock = $client->getLock($domain->domain);
            try {
                $apiPricing = $client->pricing('renew', $domain->domain);
                $renewCredits = $pricing->creditsFor($apiPricing, 'renew');
            } catch (RuntimeException) {
                $renewCredits = $pricing->creditsFromPriceString(null);
            }
        } catch (RuntimeException) {
            // Keep the page usable when the upstream API is unavailable.
        }

        return view('livewire.domains.show', [
            'domain' => $domain,
            'information' => $information,
            'lock' => $lock,
            'renewCredits' => $renewCredits,
            'websites' => auth()->user()->websites()->latest()->get(),
        ])->extends('layouts.app')->title($domain->domain);
    }
}
