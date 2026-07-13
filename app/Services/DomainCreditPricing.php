<?php

namespace App\Services;

class DomainCreditPricing
{
    /**
     * @param  'register'|'renew'|'transfer'  $type
     * @param  array<string, mixed>  $addons
     */
    public function creditsFor(array $pricing, string $type, int $regperiod = 1, array $addons = []): int
    {
        $perYear = $this->creditsFromPriceString($this->extractPrice($pricing, $type));

        return max(1, ($perYear * $regperiod) + $this->addonCredits($addons));
    }

    public function creditsFromPriceString(?string $price): int
    {
        if (blank($price)) {
            return (int) config('sites.domain_default_credits', 5);
        }

        $cents = $this->parsePriceToCents($price);
        $unit = max(1, (int) config('sites.credit_unit_cents', 2000));

        return max(1, (int) ceil($cents / $unit));
    }

    /**
     * @param  array<string, mixed>  $addons
     */
    public function addonCredits(array $addons): int
    {
        $costs = config('sites.domain_addon_credits', []);
        $total = 0;

        foreach (['dnsmanagement', 'emailforwarding', 'idprotection'] as $addon) {
            if (! empty($addons[$addon])) {
                $total += (int) ($costs[$addon] ?? 0);
            }
        }

        return $total;
    }

    /**
     * @param  'register'|'renew'|'transfer'  $type
     */
    private function extractPrice(array $pricing, string $type): ?string
    {
        if (isset($pricing[$type])) {
            return is_array($pricing[$type])
                ? (string) ($pricing[$type]['price'] ?? reset($pricing[$type]))
                : (string) $pricing[$type];
        }

        if (isset($pricing['price'])) {
            return (string) $pricing['price'];
        }

        return null;
    }

    private function parsePriceToCents(string $price): int
    {
        $normalized = preg_replace('/[^0-9.,]/', '', $price) ?? '';
        $normalized = str_replace(',', '.', $normalized);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return (int) config('sites.domain_default_credits', 5) * max(1, (int) config('sites.credit_unit_cents', 2000));
        }

        return (int) round((float) $normalized * 100);
    }
}
