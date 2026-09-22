<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp;

use App\Shared\Providers\DomainServiceProvider;

class WhatsAppServiceProvider extends DomainServiceProvider
{
    protected function domainPath(): string
    {
        return __DIR__;
    }
}
