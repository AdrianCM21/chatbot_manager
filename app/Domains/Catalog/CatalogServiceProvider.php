<?php

declare(strict_types=1);

namespace App\Domains\Catalog;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Policies\ProductPolicy;
use App\Shared\Providers\DomainServiceProvider;

class CatalogServiceProvider extends DomainServiceProvider
{
    protected function domainPath(): string
    {
        return __DIR__;
    }

    protected function policies(): array
    {
        return [
            Product::class => ProductPolicy::class,
        ];
    }
}
