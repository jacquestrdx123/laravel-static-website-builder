<?php

namespace App\Http\Controllers;

use App\Services\DomainCreditPricing;
use App\Services\HostAfricaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DomainSearchController extends Controller
{
    public function index(): View
    {
        return view('domains.search', [
            'results' => session('domain_search_results', []),
            'searchTerm' => session('domain_search_term'),
        ]);
    }

    public function search(Request $request, HostAfricaClient $client, DomainCreditPricing $creditPricing): RedirectResponse
    {
        $data = $request->validate([
            'searchTerm' => ['required', 'string', 'max:255'],
            'tldsToInclude' => ['nullable', 'array'],
            'tldsToInclude.*' => ['string', 'max:20'],
        ]);

        try {
            $lookupOptions = [];

            if (! empty($data['tldsToInclude'])) {
                $lookupOptions['tldsToInclude'] = $data['tldsToInclude'];
            }

            $lookup = $client->lookup($data['searchTerm'], $lookupOptions);

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
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('domains.search')
            ->with('domain_search_term', $data['searchTerm'])
            ->with('domain_search_results', $results);
    }

    public function suggest(Request $request, HostAfricaClient $client): JsonResponse
    {
        $data = $request->validate([
            'searchTerm' => ['required', 'string', 'max:255'],
        ]);

        try {
            $suggestions = $client->suggestions($data['searchTerm']);

            return response()->json($suggestions);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * @return list<array{domain: string, available: bool, price: ?string}>
     */
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
                        'available' => (bool) ($item['available'] ?? $item['status'] === 'available'),
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
                'available' => (bool) ($lookup['available'] ?? $lookup['status'] === 'available'),
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
