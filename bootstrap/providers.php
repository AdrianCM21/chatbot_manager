<?php

use App\Domains\Catalog\CatalogServiceProvider;
use App\Domains\Settings\SettingsServiceProvider;
use App\Domains\WhatsApp\WhatsAppServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Pgvector\Laravel\PgvectorServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    WhatsAppServiceProvider::class,
    SettingsServiceProvider::class,
    AdminPanelProvider::class,
    PgvectorServiceProvider::class,
];
