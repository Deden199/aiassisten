<?php

return [
    'provider' => env('BILLING_PROVIDER', 'stripe'),
    'default_currency' => env('BILLING_CURRENCY', 'usd'),
    'success_url' => env('BILLING_SUCCESS_URL', env('APP_URL').'/billing/return'),
    'cancel_url' => env('BILLING_CANCEL_URL', env('APP_URL').'/billing/return?cancel=1'),
    'grace_days' => env('BILLING_GRACE_DAYS', 3),
];

