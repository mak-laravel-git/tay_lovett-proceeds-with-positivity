<?php

use App\Providers\AdsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AppSettingsServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AppSettingsServiceProvider::class,
    FortifyServiceProvider::class,
    AdsServiceProvider::class,
];
