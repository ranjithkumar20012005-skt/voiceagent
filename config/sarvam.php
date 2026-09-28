<?php

/*
|--------------------------------------------------------------------------
| Sarvam Voice Agents integration
|--------------------------------------------------------------------------
|
| Every value here is read from the environment. Secrets (api_key,
| webhook_token) must NEVER be rendered into Blade, JavaScript or API
| responses -- they are used exclusively by server-side code in
| App\Services\SarvamVoiceService.
|
*/

return [

    // ---------------------------------------------------------------
    // Credentials & endpoint
    // ---------------------------------------------------------------
    'api_key'      => env('SARVAM_API_KEY'),
    'base_url'     => rtrim((string) env('SARVAM_BASE_URL', 'https://apps.sarvam.ai'), '/'),
    'org_id'       => env('SARVAM_ORG_ID'),
    'workspace_id' => env('SARVAM_WORKSPACE_ID'),

    // ---------------------------------------------------------------
    // The already-built agent inside Sarvam
    // ---------------------------------------------------------------
    'app_id'             => env('SARVAM_APP_ID'),
    'app_version'        => env('SARVAM_APP_VERSION'),
    'app_type'           => env('SARVAM_APP_TYPE', 'agent'),
    'connection_id'      => env('SARVAM_CONNECTION_ID'),
    'agent_phone_number' => env('SARVAM_AGENT_PHONE_NUMBER'),
    'campaign_id'        => env('SARVAM_CAMPAIGN_ID'),

    // ---------------------------------------------------------------
    // Webhook. Sarvam must be able to reach this host; falls back to APP_URL.
    // ---------------------------------------------------------------
    'webhook_token' => env('SARVAM_WEBHOOK_TOKEN'),
    'webhook_url'   => env('SARVAM_WEBHOOK_URL'),

    // ---------------------------------------------------------------
    // HTTP client behaviour
    // ---------------------------------------------------------------
    'http' => [
        'connect_timeout' => (int) env('SARVAM_CONNECT_TIMEOUT', 10),
        'timeout'         => (int) env('SARVAM_TIMEOUT', 30),
        'retry_times'     => (int) env('SARVAM_RETRY_TIMES', 3),
        'retry_sleep_ms'  => (int) env('SARVAM_RETRY_SLEEP_MS', 500),
    ],

    // ---------------------------------------------------------------
    // Campaign defaults -- deliberately conservative.
    // ---------------------------------------------------------------
    'campaign' => [
        'attempts_per_second' => (float) env('SARVAM_ATTEMPTS_PER_SECOND', 1.0),
        'max_calls_per_run'   => (int) env('SARVAM_MAX_CALLS_PER_RUN', 200),
        'cohort_chunk_size'   => 1000, // hard API limit: 1-1000 users per stream request
        'retry' => [
            'max_retries'            => (int) env('SARVAM_MAX_RETRIES', 2),
            'retry_interval_minutes' => (int) env('SARVAM_RETRY_INTERVAL_MINUTES', 60),
            'retry_on_busy'          => (bool) env('SARVAM_RETRY_ON_BUSY', true),
            'retry_on_no_answer'     => (bool) env('SARVAM_RETRY_ON_NO_ANSWER', true),
            'retry_on_failed'        => (bool) env('SARVAM_RETRY_ON_FAILED', false),
        ],
        'window' => [
            'start' => env('SARVAM_WINDOW_START', '09:00'),
            'end'   => env('SARVAM_WINDOW_END', '19:00'),
            'days'  => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ],
    ],

    // ---------------------------------------------------------------
    // Telephony / phone normalisation
    // ---------------------------------------------------------------
    'default_country_code' => env('SARVAM_DEFAULT_COUNTRY_CODE', '91'),

    // ---------------------------------------------------------------
    // Languages the configured agent supports (Sarvam enum values).
    // ---------------------------------------------------------------
    'languages' => [
        'English', 'Hindi', 'Bengali', 'Gujarati', 'Kannada', 'Malayalam',
        'Marathi', 'Odia', 'Punjabi', 'Sanskrit', 'Tamil', 'Telugu', 'Assamese',
    ],
    'default_language' => env('SARVAM_DEFAULT_LANGUAGE', 'Hindi'),

    /*
    |----------------------------------------------------------------
    | Agent variable mapping  (Sarvam variable => local customer field)
    |----------------------------------------------------------------
    | Single source of truth for what gets sent to the agent as
    | agent_variables / app_variables. Nothing is hardcoded in the service
    | or jobs -- change the mapping here and the whole pipeline follows.
    | Keys must match the variable names configured on the agent in Sarvam.
    |
    | Only variables the agent actually declares may be sent -- the API rejects
    | the whole call with 422 "Agent variables {...} not found" otherwise.
    |
    | Verified against agent Renewal-ins-2f5d65f0-ab95 (v1), which declares
    | exactly: user_name, policy_number, registered_mobile.
    |
    | Adding a variable here requires adding it on the agent in Sarvam first.
    | Language is NOT a variable -- it is sent via app_overrides.
    */
    'agent_variables' => [
        'user_name'         => 'name',
        'policy_number'     => 'policy_number',
        'registered_mobile' => 'registered_mobile',
    ],

    /*
    |----------------------------------------------------------------
    | Output variables read back off a completed call.
    | Sarvam agent output variable name => our CallAttempt column.
    */
    'output_variables' => [
        'call_disposition'       => 'call_disposition',
        'lead_generated'         => 'lead_generated',
        'callback_required'      => 'callback_required',
        'callback_at'            => 'callback_at',
        'renewal_interest'       => 'renewal_interest',
        'payment_link_requested' => 'payment_link_requested',
        'customer_notes'         => 'customer_notes',
    ],

    // Sarvam connectivity status => our canonical connectivity status.
    'connectivity_map' => [
        'connected' => 'connected',
        'no_answer' => 'no_answer',
        'noanswer'  => 'no_answer',
        'busy'      => 'busy',
        'failed'    => 'failed',
    ],

    // Sarvam call_disposition output variable => our canonical business outcome.
    // Anything not listed falls through to 'unknown' -- never to a negative outcome.
    'disposition_map' => [
        'interested'      => 'interested',
        'callback'        => 'callback',
        'call_back'       => 'callback',
        'not_interested'  => 'not_interested',
        'already_renewed' => 'already_renewed',
        'renewed'         => 'already_renewed',
        'wrong_person'    => 'wrong_person',
        'wrong_number'    => 'wrong_person',
        'do_not_call'     => 'do_not_call',
        'dnc'             => 'do_not_call',
        'escalated'       => 'escalated',
    ],

    /*
    |----------------------------------------------------------------
    | Import column aliases -- used to auto-suggest a column mapping when
    | a client uploads a sheet whose headings differ from ours. The user
    | can always override the suggestion in the mapping step.
    */
    'import_aliases' => [
        'customer_identifier' => ['customer_id', 'customerid', 'id', 'cust_id', 'customer code'],
        'name'                => ['user_name', 'username', 'customer_name', 'name', 'full name', 'customer'],
        'phone_number'        => ['phone_number', 'phone', 'mobile', 'contact', 'mobile_number', 'phone no'],
        'policy_number'       => ['policy_number', 'policy', 'policy_no', 'policyno'],
        'registered_mobile'   => ['registered_mobile', 'reg_mobile', 'registered mobile', 'alt_mobile'],
        'policy_expiry_date'  => ['policy_expiry_date', 'expiry', 'expiry_date', 'due_date', 'renewal_date'],
        'renewal_premium'     => ['renewal_premium', 'premium', 'amount', 'renewal amount'],
        'preferred_language'  => ['preferred_language', 'language', 'lang'],
        'notes'               => ['notes', 'remarks', 'comment'],
    ],

    // ---------------------------------------------------------------
    // Upload limits
    // ---------------------------------------------------------------
    'upload' => [
        'max_file_kb'          => (int) env('SARVAM_MAX_UPLOAD_KB', 10240),             // 10 MB
        'max_rows'             => (int) env('SARVAM_MAX_IMPORT_ROWS', 50000),
        'zip_max_uncompressed' => (int) env('SARVAM_ZIP_MAX_UNCOMPRESSED', 52428800),   // 50 MB
        'zip_max_ratio'        => (int) env('SARVAM_ZIP_MAX_RATIO', 120),               // zip-bomb guard
    ],
];
