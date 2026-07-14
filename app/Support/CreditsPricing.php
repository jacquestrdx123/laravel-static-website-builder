<?php

namespace App\Support;

class CreditsPricing
{
    public function creditValueZar(): float
    {
        return (float) config('credits.credit_value_zar', 50);
    }

    public function currencySymbol(): string
    {
        return (string) config('credits.currency_symbol', 'R');
    }

    public function toZar(float|int $credits): float
    {
        return round((float) $credits * $this->creditValueZar(), 2);
    }

    public function formatZar(float|int $credits): string
    {
        $amount = $this->toZar($credits);

        if (fmod($amount, 1.0) === 0.0) {
            return $this->currencySymbol().number_format($amount, 0, '.', '');
        }

        return $this->currencySymbol().number_format($amount, 2, '.', '');
    }

    public function formatCredits(float|int $credits): string
    {
        $value = (float) $credits;

        if (fmod($value, 1.0) === 0.0) {
            $label = (string) (int) $value;
        } else {
            $label = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        }

        return $label.' '.($value === 1.0 ? 'credit' : 'credits');
    }

    /**
     * Catalog for the pricing page, in display order.
     *
     * @return array{
     *     credit_value_zar: float,
     *     currency: string,
     *     currency_symbol: string,
     *     items: list<array<string, mixed>>
     * }
     */
    public function catalog(): array
    {
        $websiteGeneration = config('credits.website_generation');
        $websiteHosting = config('credits.website_hosting');
        $editing = config('credits.editing_without_ai');
        $newsletter = config('credits.newsletter');
        $poster = config('credits.marketing_poster');

        return [
            'credit_value_zar' => $this->creditValueZar(),
            'currency' => (string) config('credits.currency', 'ZAR'),
            'currency_symbol' => $this->currencySymbol(),
            'items' => [
                [
                    'key' => 'website_generation',
                    'label' => $websiteGeneration['label'],
                    'description' => $websiteGeneration['description'],
                    'billing' => 'one_time',
                    'credits' => (float) $websiteGeneration['credits'],
                    'credits_label' => $this->formatCredits($websiteGeneration['credits']),
                    'zar_label' => $this->formatZar($websiteGeneration['credits']).' one-time',
                ],
                [
                    'key' => 'website_hosting',
                    'label' => $websiteHosting['label'],
                    'description' => $websiteHosting['description'],
                    'billing' => 'monthly',
                    'credits_per_month' => (float) $websiteHosting['credits_per_month'],
                    'credits_label' => $this->formatCredits($websiteHosting['credits_per_month']).' / month',
                    'zar_label' => $this->formatZar($websiteHosting['credits_per_month']).' / month',
                ],
                [
                    'key' => 'editing_without_ai',
                    'label' => $editing['label'],
                    'description' => $editing['description'],
                    'billing' => 'monthly_or_yearly',
                    'credits_per_month' => (float) $editing['credits_per_month'],
                    'credits_per_year' => (float) $editing['credits_per_year'],
                    'credits_label' => $this->formatCredits($editing['credits_per_month']).' / month or '
                        .$this->formatCredits($editing['credits_per_year']).' / year',
                    'zar_label' => $this->formatZar($editing['credits_per_month']).' / month or '
                        .$this->formatZar($editing['credits_per_year']).' / year',
                ],
                [
                    'key' => 'newsletter',
                    'label' => $newsletter['label'],
                    'description' => $newsletter['description'],
                    'billing' => 'monthly',
                    'hosting_credits_per_month' => (float) $newsletter['hosting_credits_per_month'],
                    'included_emails_per_month' => (int) $newsletter['included_emails_per_month'],
                    'extra_block_credits' => (float) $newsletter['extra_block_credits'],
                    'extra_block_size' => (int) $newsletter['extra_block_size'],
                    'credits_label' => $this->formatCredits($newsletter['hosting_credits_per_month'])
                        .' / month + '.$newsletter['included_emails_per_month'].' free emails',
                    'zar_label' => $this->formatZar($newsletter['hosting_credits_per_month']).' / month',
                    'extra_emails_label' => $this->formatCredits($newsletter['extra_block_credits'])
                        .' per '.$newsletter['extra_block_size'].' emails',
                    'extra_emails_zar_label' => $this->formatZar($newsletter['extra_block_credits'])
                        .' per '.$newsletter['extra_block_size'],
                    'features' => $newsletter['features'],
                ],
                [
                    'key' => 'marketing_poster',
                    'label' => $poster['label'],
                    'description' => $poster['description'],
                    'billing' => 'one_time',
                    'credits' => (float) $poster['credits'],
                    'retries_included' => (int) $poster['retries_included'],
                    'credits_label' => $this->formatCredits($poster['credits'])
                        .' + '.$poster['retries_included'].' retries',
                    'zar_label' => $this->formatZar($poster['credits']),
                ],
            ],
        ];
    }
}
