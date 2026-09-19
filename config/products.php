<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First-Party Products
    |--------------------------------------------------------------------------
    |
    | The 3AG applications that sign users in through this identity provider.
    | Each one is seeded as a first-party OAuth client, which means a signed-in
    | user is never shown a consent screen for it. The base URL decides the
    | redirect URIs, so a new environment only has to set these variables.
    |
    */

    'clients' => [

        'salesreport' => [
            'name' => 'SalesReport',
            'url' => env('CLIENT_SALESREPORT_URL', 'http://localhost:8001'),
        ],

        'productsyncmanager' => [
            'name' => 'ProductSyncManager',
            'url' => env('CLIENT_PRODUCTSYNCMANAGER_URL', 'http://localhost:8002'),
        ],

        'complianceplatform' => [
            'name' => 'CompliancePlatform',
            'url' => env('CLIENT_COMPLIANCEPLATFORM_URL', 'http://localhost:8003'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Client Callback Paths
    |--------------------------------------------------------------------------
    |
    | Appended to each product's base URL. Every product app uses the same
    | routes, so these are defined once rather than per client.
    |
    */

    'callback_path' => '/auth/accounts/callback',

    'post_logout_path' => '/',

];
