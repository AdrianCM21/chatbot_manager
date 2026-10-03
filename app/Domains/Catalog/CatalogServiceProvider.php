<?php

declare(strict_types=1);

namespace App\Domains\Catalog;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
