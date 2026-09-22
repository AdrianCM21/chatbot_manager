<?php

declare(strict_types=1);

namespace App\Domains\Settings;

use App\Shared\Providers\DomainServiceProvider;

class SettingsServiceProvider extends DomainServiceProvider
{
    protected function domainPath(): string
    {
        return __DIR__;
    }
}
