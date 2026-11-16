<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo de operación de pagos
    |--------------------------------------------------------------------------
    | Valores: mock | sandbox | live
    | "live" requiere APP_ENV=production y está deshabilitado hasta autorización
    | expresa en una fase posterior.
    */
    'mode' => env('PAYMENT_MODE', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Pasarela por defecto
    |--------------------------------------------------------------------------
    | Valores: mock | payphone | deuna | bank_transfer | cash
    */
    'default_gateway' => env('PAYMENT_GATEWAY', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Moneda
    |--------------------------------------------------------------------------
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Mock Gateway
    |--------------------------------------------------------------------------
    */
    'mock' => [
        'success_rate' => (float) env('MOCK_SUCCESS_RATE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPhone (sandbox stub — credenciales reales no incluidas)
    |--------------------------------------------------------------------------
    */
    'payphone' => [
        'token'       => env('PAYPHONE_TOKEN', ''),
        'store_id'    => env('PAYPHONE_STORE_ID', ''),
        'sandbox_url' => 'https://pay.payphonetodoesposible.com/api',
        'live_url'    => 'https://pay.payphonetodoesposible.com/api',
    ],

    /*
    |--------------------------------------------------------------------------
    | DEUNA (sandbox stub — credenciales reales no incluidas)
    |--------------------------------------------------------------------------
    */
    'deuna' => [
        'api_key'        => env('DEUNA_API_KEY', ''),
        'store_code'     => env('DEUNA_STORE_CODE', ''),
        'webhook_secret' => env('DEUNA_WEBHOOK_SECRET', env('PAYMENT_WEBHOOK_SECRET', '')),
        'sandbox'        => (bool) env('DEUNA_SANDBOX', true),
        'sandbox_url'    => 'https://api-staging.deuna.com',
        'live_url'       => 'https://api.deuna.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Idempotencia — TTL en segundos para rechazar llaves duplicadas
    |--------------------------------------------------------------------------
    */
    'idempotency_ttl' => (int) env('PAYMENT_IDEMPOTENCY_TTL', 86400),

];
