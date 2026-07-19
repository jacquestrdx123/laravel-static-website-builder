<?php

namespace App\Livewire\Domains;

use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use App\Support\DomainContactBuilder;
use Livewire\Component;
use RuntimeException;

class Register extends Component
{
    public function mount(): mixed
    {
        $domain = strtolower((string) request()->query('domain', ''));

        if (blank($domain)) {
            session()->flash('error', 'Choose a domain from search results first.');

            return $this->redirect(route('domains.search'), navigate: true);
        }

        return null;
    }

    public function render(HostAfricaClient $client, DomainCreditPricing $pricing)
    {
        $domain = strtolower((string) request()->query('domain', ''));
        $creditCost = $this->quoteCredits($client, $pricing, 'register', $domain);

        return view('livewire.domains.register', [
            'domain' => $domain,
            'mode' => 'register',
            'creditCost' => $creditCost,
            'defaultContact' => DomainContactBuilder::fromUser(auth()->user()),
            'defaultNameservers' => DomainContactBuilder::defaultNameservers(),
            'websites' => auth()->user()->websites()->latest()->get(),
        ])->extends('layouts.app')->title('Register domain');
    }

    private function quoteCredits(
        HostAfricaClient $client,
        DomainCreditPricing $pricing,
        string $type,
        string $domain,
    ): int {
        try {
            $apiPricing = $client->pricing($type, $domain);

            return $pricing->creditsFor($apiPricing, $type);
        } catch (RuntimeException) {
            return $pricing->creditsFromPriceString(null);
        }
    }
}
