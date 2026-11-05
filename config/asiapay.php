<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AsiaPay merchant
    |--------------------------------------------------------------------------
    |
    | Matches a key in the "merchants" array below.
    |
    */
    'default' => env('ASIAPAY_MERCHANT', 'default'),

    'token_cache_ttl_seconds' => (int) env('ASIAPAY_TOKEN_TTL', 3300),

    /*
    |--------------------------------------------------------------------------
    | AsiaPay merchants (Iraq — asiapay.iq)
    |--------------------------------------------------------------------------
    |
    | Official integration guide: https://www.asiapay.iq/integration
    |
    | base_url: the "Domain URL" AsiaPay assigns (sandbox default below).
    | private_key: JWT signing secret (HS256) for request/response/webhooks.
    |
    */
    'merchants' => [
        'default' => [
            'environment' => env('ASIAPAY_ENVIRONMENT', 'sandbox'),
            'base_url' => env('ASIAPAY_BASE_URL', 'https://apitest.asiapay.iq:5443/apiaccess'),
            'app_key' => env('ASIAPAY_APP_KEY'),
            'app_secret' => env('ASIAPAY_APP_SECRET'),
            'private_key' => env('ASIAPAY_PRIVATE_KEY'),
            'app_id' => env('ASIAPAY_APP_ID'),
            'merch_code' => env('ASIAPAY_MERCH_CODE'),
            'redirect_url' => env('ASIAPAY_REDIRECT_URL'),
            'notify_url' => env('ASIAPAY_NOTIFY_URL'),
        ],
    ],

    'currency' => env('ASIAPAY_CURRENCY', 'IQD'),

    'business_type' => env('ASIAPAY_BUSINESS_TYPE', 'BuyGoods'),

    'timeout_express' => env('ASIAPAY_TIMEOUT_EXPRESS', '30m'),

    /*
    |--------------------------------------------------------------------------
    | Webhook acknowledgment
    |--------------------------------------------------------------------------
    |
    | Body returned to AsiaPay after a verified notification. Confirm the
    | exact format with devops@asiapay.iq for your merchant account.
    |
    */
    'webhook' => [
        'ack_body' => env('ASIAPAY_WEBHOOK_ACK', 'SUCCESS'),
    ],

    'http' => [
        'timeout' => (int) env('ASIAPAY_HTTP_TIMEOUT', 30),
        'retry_times' => (int) env('ASIAPAY_HTTP_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('ASIAPAY_HTTP_RETRY_SLEEP_MS', 200),
    ],

    'sync' => [
        'limit' => 100,
    ],

];
