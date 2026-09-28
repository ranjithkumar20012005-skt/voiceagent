<?php

/*
|--------------------------------------------------------------------------
| Public pricing
|--------------------------------------------------------------------------
|
| The marketing page reads its plans from here. Prices are *not* invented:
| each one is null unless an environment variable supplies it, and the card
| then falls back to "Contact Sales". Set e.g.
|
|   PRICING_STARTER_PRICE="₹14,999"
|   PRICING_STARTER_PERIOD="per month"
|
| to publish a real figure.
|
*/

return [

    'currency_note' => env('PRICING_NOTE'),

    'plans' => [
        [
            'name'        => 'Starter',
            'tagline'     => 'For smaller call volumes',
            'price'       => env('PRICING_STARTER_PRICE'),
            'period'      => env('PRICING_STARTER_PERIOD', 'per month'),
            'featured'    => false,
            'features'    => [
                'Outbound AI voice calls',
                'Automatic outcome detection',
                'Call transcripts',
                'Email support',
            ],
        ],
        [
            'name'        => 'Business',
            'tagline'     => 'For growing teams',
            'price'       => env('PRICING_BUSINESS_PRICE'),
            'period'      => env('PRICING_BUSINESS_PERIOD', 'per month'),
            'featured'    => true,
            'features'    => [
                'Everything in Starter',
                'Higher call volume',
                'Lead qualification & follow-ups',
                'Multi-language conversations',
                'Priority support',
            ],
        ],
        [
            'name'        => 'Enterprise',
            'tagline'     => 'Custom call volume and support',
            'price'       => env('PRICING_ENTERPRISE_PRICE'),
            'period'      => env('PRICING_ENTERPRISE_PERIOD'),
            'featured'    => false,
            'features'    => [
                'Everything in Business',
                'Dedicated onboarding',
                'Custom integrations',
                'Service-level agreement',
            ],
        ],
    ],

    // Where "Contact Sales" points. Falls back to a mailto: built from this.
    'contact_email' => env('PRICING_CONTACT_EMAIL', 'sales@example.com'),
];
