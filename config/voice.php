<?php

/*
|--------------------------------------------------------------------------
| Voice engine
|--------------------------------------------------------------------------
|
| Which engine runs conversations, and which provider implements it. Today the
| conversation is hosted by the provider end to end; `engine` exists so that
| swapping to a self-run speech pipeline later is a configuration change plus a
| new set of adapters, not a schema or controller rewrite.
|
| Nothing in here is customer-facing. Credentials live only in config/sarvam.php
| and are read server-side.
|
*/

return [

    'engine' => env('VOICE_ENGINE', 'hosted'),

    'provider' => env('VOICE_PROVIDER', 'sarvam'),

    /*
    | Adapter bindings per provider. Resolved through the container, so a second
    | provider is added here rather than by editing controllers.
    */
    'providers' => [
        'sarvam' => [
            'agent'    => App\Services\Provider\Sarvam\SarvamVoiceAgentProvider::class,
            'calling'  => App\Services\Provider\Sarvam\SarvamHostedCallingProvider::class,
            'numbers'  => App\Services\Provider\Sarvam\SarvamPhoneNumberProvider::class,
            'tests'    => App\Services\Provider\Sarvam\SarvamTestProvider::class,
        ],
    ],

    /*
    | Voices offered in the Create Agent form.
    |
    | The keys are the platform's real speaker identifiers and the labels are what
    | a customer sees -- so a customer picks "Kavya (female, Hindi)" and never
    | learns whose catalogue it came from. Read from the platform's own voice list
    | for this workspace, which is smaller than its full catalogue.
    |
    | `language` is the voice's preferred language, used to filter the dropdown
    | once a primary language is chosen.
    */
    'voices' => [
        'kavya'     => ['label' => 'Kavya - female, warm',          'gender' => 'female', 'language' => 'Hindi'],
        'priya'     => ['label' => 'Priya - female, professional',  'gender' => 'female', 'language' => 'Hindi'],
        'ritu'      => ['label' => 'Ritu - female, friendly',       'gender' => 'female', 'language' => 'Hindi'],
        'pooja'     => ['label' => 'Pooja - female, calm',          'gender' => 'female', 'language' => 'Hindi'],
        'simran'    => ['label' => 'Simran - female, bright',       'gender' => 'female', 'language' => 'Hindi'],
        'ishita'    => ['label' => 'Ishita - female, clear',        'gender' => 'female', 'language' => 'Hindi'],
        'shreya'    => ['label' => 'Shreya - female, gentle',       'gender' => 'female', 'language' => 'Hindi'],
        'roopa'     => ['label' => 'Roopa - female, mature',        'gender' => 'female', 'language' => 'Hindi'],
        'aditya'    => ['label' => 'Aditya - male, professional',   'gender' => 'male',   'language' => 'Hindi'],
        'rahul'     => ['label' => 'Rahul - male, warm',            'gender' => 'male',   'language' => 'Hindi'],
        'rohan'     => ['label' => 'Rohan - male, friendly',        'gender' => 'male',   'language' => 'Hindi'],
        'amit'      => ['label' => 'Amit - male, steady',           'gender' => 'male',   'language' => 'Hindi'],
        'dev'       => ['label' => 'Dev - male, clear',             'gender' => 'male',   'language' => 'Hindi'],
        'varun'     => ['label' => 'Varun - male, bright',          'gender' => 'male',   'language' => 'Hindi'],
        'kabir'     => ['label' => 'Kabir - male, calm',            'gender' => 'male',   'language' => 'Hindi'],
        'shubh'     => ['label' => 'Shubh - male, neutral',         'gender' => 'male',   'language' => 'Hindi'],
        'amelia'    => ['label' => 'Amelia - female, English',      'gender' => 'female', 'language' => 'English'],
        'sophia'    => ['label' => 'Sophia - female, English',      'gender' => 'female', 'language' => 'English'],
    ],

    /*
    | Languages the Create Agent form offers. Taken from what the platform's
    | agents actually support in this workspace.
    */
    'languages' => [
        'English', 'Hindi', 'Telugu', 'Tamil', 'Kannada', 'Malayalam',
        'Marathi', 'Bengali', 'Gujarati', 'Punjabi', 'Odia',
    ],

    /*
    | What a customer is told when the provider fails. Diagnostics stay in the
    | logs; the wording below is all that reaches the browser.
    */
    'messages' => [
        'unavailable'   => 'Voice service is temporarily unavailable.',
        'call_failed'   => 'Calling could not be started.',
        'number_taken'  => 'Phone number is currently unavailable.',
        'not_configured' => 'Voice AI is not configured.',
    ],

    /*
    | Billing. customer_rate is what we charge; the provider's own cost is
    | recorded separately on the usage row and never shown.
    */
    'billing' => [
        'currency'            => env('BILLING_CURRENCY', 'INR'),
        'customer_rate_per_minute' => env('BILLING_RATE_PER_MINUTE', '5.00'),
        'minimum_billable_seconds' => (int) env('BILLING_MIN_SECONDS', 1),
    ],
];
