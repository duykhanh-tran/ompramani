<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SePay Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Official SePay Payment Gateway (VietQR / Cards / Bank Transfers)
    | SDK: sepay/sepay-pg v1.0.0
    |
    */

    'merchant_id' => env('SEPAY_MERCHANT_ID'),

    'secret_key' => env('SEPAY_MERCHANT_SECRET_KEY'),

    'ipn_secret_key' => env('SEPAY_IPN_SECRET_KEY'),

    'environment' => env('SEPAY_ENVIRONMENT', 'sandbox'),

    'frontend_url' => env('FRONTEND_URL', env('APP_FRONTEND_URL', 'https://ompramani.vercel.app')),

    'success_url' => env('SEPAY_SUCCESS_URL'),

    'cancel_url' => env('SEPAY_CANCEL_URL'),

    'error_url' => env('SEPAY_ERROR_URL'),
];
