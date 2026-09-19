<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 3AG Applications
    |--------------------------------------------------------------------------
    |
    | The applications that authenticate through 3AG Accounts. Entries without
    | a configured URL are hidden from the home page, which lets a developer
    | run only the applications they are currently working on.
    |
    */

    'apps' => [
        [
            'name' => 'SalesReport',
            'description' => 'Sales performance reporting and dashboards.',
            'url' => env('SALESREPORT_URL'),
        ],
        [
            'name' => 'ProductSync',
            'description' => 'Catalogue and inventory synchronisation.',
            'url' => env('PRODUCTSYNC_URL'),
        ],
        [
            'name' => 'Compliance Platform',
            'description' => 'Regulatory checks and audit trails.',
            'url' => env('COMPLIANCEPLATFORM_URL'),
        ],
    ],

];
