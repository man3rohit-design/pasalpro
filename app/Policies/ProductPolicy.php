<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->role->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin)
            && $product->saleItems()->doesntExist()
            && $product->purchaseItems()->doesntExist();
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }
}
