<?php

namespace App\Livewire\Domains;

use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Livewire\Component;
use RuntimeException;

class Transfer extends Component
{
    public function render(HostAfricaClient $client, DomainCreditPricing $pricing)
    {
        $domain = strtolower((string) request()->query('domain', ''));
        $creditCost = null;

        if (filled($domain)) {
            try {
                $apiPricing = $client->pricing('transfer', $domain);
                $creditCost = $pricing->creditsFor($apiPricing, 'transfer');
            } catch (RuntimeException) {
                $creditCost = $pricing->creditsFromPriceString(null);
            }
        }

        return view('livewire.domains.register', [
            'domain' => $domain,
            'mode' => 'transfer',
            'creditCost' => $creditCost,
            'defaultContact' => DomainContactBuilder::fromUser(auth()->user()),
            'defaultNameservers' => DomainContactBuilder::defaultNameservers(),
            'websites' => auth()->user()->websites()->latest()->get(),
        ])->extends('layouts.app')->title('Transfer domain');
    }
}
