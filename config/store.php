<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront / email branding
    |--------------------------------------------------------------------------
    |
    | Defaults to APP_NAME so transactional emails and the storefront stay
    | aligned with the application name unless STORE_* overrides are set.
    |
    */

    'name' => env('STORE_NAME', env('APP_NAME', 'Commerce')),

    'tagline' => env('STORE_TAGLINE', 'Est. Handcrafted Goods'),

    'care_email' => env('STORE_CARE_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    'location' => env('STORE_LOCATION', 'Islamabad, Pakistan'),

    'currency' => env('STORE_CURRENCY', 'PKR'),

    'preferences_url' => env('STORE_EMAIL_PREFERENCES_URL'),

];
