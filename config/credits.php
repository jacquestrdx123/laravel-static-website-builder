<?php

/**
 * Locked credit pricing. Amounts are in credits unless noted otherwise.
 * Currency conversion uses credit_value_zar (South African Rand).
 */
return [
    'credit_value_zar' => 50,
    'currency' => 'ZAR',
    'currency_symbol' => 'R',
    'website_generation' => [
        'credits' => 15,
        'label' => 'Website Generation',
        'description' => 'One-time generation of a new website.',
    ],
    'website_hosting' => [
        'credits_per_month' => 3,
        'label' => 'Website Hosting',
        'description' => 'Hosting for one website, billed per month.',
    ],
    'editing_without_ai' => [
        'credits_per_month' => 1.5,
        'credits_per_year' => 12,
        'label' => 'Editing without AI',
        'description' => 'Manual editing access without AI assistance.',
    ],
    'newsletter' => [
        'hosting_credits_per_month' => 6,
        'included_emails_per_month' => 500,
        'extra_block_credits' => 2,
        'extra_block_size' => 500,
        'label' => 'Newsletter functionality',
        'description' => 'Professional email service with advanced reporting: opens, link clicks, and delivery insights. Hosting includes 500 free emails per month.',
        'features' => [
            'Professional email delivery',
            'Advanced reporting and analytics',
            'See who opened your newsletter',
            'See who clicked a link',
            '500 free emails included each month',
        ],
    ],
    'marketing_poster' => [
        'credits' => 2,
        'retries_included' => 2,
        'label' => 'Marketing poster',
        'description' => 'Generate a marketing poster with two included retries.',
    ],
];
