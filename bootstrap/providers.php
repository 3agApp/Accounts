<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use OpenIDConnect\Laravel\PassportServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PassportServiceProvider::class,
];
