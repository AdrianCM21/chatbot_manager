<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Product;
use App\Models\User;

class ProductPolicy
{
    // TODO: restringir por rol cuando se defina el modelo de permisos del panel admin.

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Product $product): bool
    {
        return true;
    }

    public function delete(User $user, Product $product): bool
    {
        return true;
    }
}
