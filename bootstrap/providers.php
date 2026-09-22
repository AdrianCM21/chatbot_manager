<?php

use App\Providers\AppServiceProvider;
use App\Providers\DomainRegistryServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Pgvector\Laravel\PgvectorServiceProvider;

return [
    AppServiceProvider::class,
    DomainRegistryServiceProvider::class,
    AdminPanelProvider::class,
    PgvectorServiceProvider::class,
];
