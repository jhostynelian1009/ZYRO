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
    | PayPhone
    |--------------------------------------------------------------------------
    | Confirmación oficial: POST {sandbox_url}/button/V2/Confirm
    | Cuerpo: { "id": int, "clientTxId": string }
    */
    'payphone' => [
        'token'        => env('PAYPHONE_TOKEN', ''),
        'store_id'     => env('PAYPHONE_STORE_ID', ''),
        'response_url' => env('PAYPHONE_RESPONSE_URL', ''),
        'sandbox_url'  => env('PAYPHONE_SANDBOX_URL', 'https://pay.payphonetodoesposible.com/api'),
        'live_url'     => env('PAYPHONE_LIVE_URL', 'https://pay.payphonetodoesposible.com/api'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DEUNA
    |--------------------------------------------------------------------------
    | Sandbox oficial: https://api.sandbox.deuna.io
    | Flujo comercial ZYRO: tokenizar orden (POST /merchants/orders) y redirigir
    | al checkout. POST /v2/merchants/orders/purchase es Direct API (payment_source)
    | y no sustituye el flujo de redirección.
    */
    'deuna' => [
        'api_key'        => env('DEUNA_API_KEY', env('DEUNA_PRIVATE_KEY', '')),
        'private_key'    => env('DEUNA_PRIVATE_KEY', env('DEUNA_API_KEY', '')),
        'store_code'     => env('DEUNA_STORE_CODE', ''),
        'merchant_id'    => env('DEUNA_MERCHANT_ID', ''),
        'webhook_secret' => env('DEUNA_WEBHOOK_SECRET', env('PAYMENT_WEBHOOK_SECRET', '')),
        'sandbox'        => (bool) env('DEUNA_SANDBOX', true),
        'sandbox_url'    => env('DEUNA_SANDBOX_URL', 'https://api.sandbox.deuna.io'),
        'live_url'       => env('DEUNA_LIVE_URL', 'https://api.deuna.io'),
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
