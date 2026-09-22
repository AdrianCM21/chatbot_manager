<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DomainRegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $domainsPath = app_path('Domains');

        if (! is_dir($domainsPath)) {
            return;
        }

        foreach (glob($domainsPath.'/*', GLOB_ONLYDIR) ?: [] as $domainPath) {
            $domainName = basename($domainPath);
            $providerClass = "App\\Domains\\{$domainName}\\{$domainName}ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}
