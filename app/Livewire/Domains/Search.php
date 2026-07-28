<?php

namespace App\Livewire\Domains;

use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('Search domains')]
class Search extends Component
{
    public string $searchTerm = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public function mount(): void
    {
        $this->searchTerm = (string) session('domain_search_term', '');
        $this->results = session('domain_search_results', []);
    }

    public function search(HostAfricaClient $client, DomainCreditPricing $creditPricing): void
    {
        $data = $this->validate([
            'searchTerm' => ['required', 'string', 'max:255'],
        ]);

        try {
            $lookup = $client->lookup($data['searchTerm']);
            $results = $this->normalizeLookupResults($lookup, $data['searchTerm']);

            foreach ($results as &$result) {
                if ($result['available']) {
                    try {
                        $pricing = $client->pricing('register', $result['domain']);
                        $result['price'] = $this->extractRegisterPrice($pricing);
                        $result['credits'] = $creditPricing->creditsFor($pricing, 'register');
                    } catch (RuntimeException) {
                        $result['price'] = null;
                        $result['credits'] = $creditPricing->creditsFromPriceString(null);
                    }
                }
            }
            unset($result);
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->results = $results;
        session([
            'domain_search_term' => $data['searchTerm'],
            'domain_search_results' => $results,
        ]);
    }

    public function render()
    {
        return view('livewire.domains.search')->extends('layouts.app');
    }

    /** @return list<array{domain: string, available: bool, price: ?string}> */
    private function normalizeLookupResults(array $lookup, string $searchTerm): array
    {
        if (isset($lookup['results']) && is_array($lookup['results'])) {
            return collect($lookup['results'])
                ->map(function ($item) {
                    if (is_string($item)) {
                        return [
                            'domain' => $item,
                            'available' => true,
                            'price' => null,
                        ];
                    }

                    return [
                        'domain' => (string) ($item['domain'] ?? $item['domainName'] ?? ''),
                        'available' => (bool) ($item['available'] ?? ($item['status'] ?? null) === 'available'),
                        'price' => isset($item['price']) ? (string) $item['price'] : null,
                    ];
                })
                ->filter(fn (array $item) => filled($item['domain']))
                ->values()
                ->all();
        }

        if (isset($lookup['domain'])) {
            return [[
                'domain' => (string) $lookup['domain'],
                'available' => (bool) ($lookup['available'] ?? ($lookup['status'] ?? null) === 'available'),
                'price' => isset($lookup['price']) ? (string) $lookup['price'] : null,
            ]];
        }

        return [[
            'domain' => $searchTerm,
            'available' => (bool) ($lookup['available'] ?? false),
            'price' => isset($lookup['price']) ? (string) $lookup['price'] : null,
        ]];
    }

    private function extractRegisterPrice(array $pricing): ?string
    {
        foreach (['register', 'price', 'amount', '1'] as $key) {
            if (isset($pricing[$key])) {
                return is_array($pricing[$key])
                    ? (string) ($pricing[$key]['register'] ?? $pricing[$key]['price'] ?? reset($pricing[$key]))
                    : (string) $pricing[$key];
            }
        }

        return null;
    }
}
