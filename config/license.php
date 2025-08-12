<?php

return [
    // Bypass license check (for local dev, CI)
    'bypass' => (bool) env('LICENSE_BYPASS', false),

    // Grace period in days
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 7),

    // Envato personal token for API verification
    'envato_token' => env('ENVATO_API_TOKEN'),
];
