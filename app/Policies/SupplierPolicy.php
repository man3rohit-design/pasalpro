<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole(UserRole::Admin) && $supplier->purchaseOrders()->doesntExist();
    }
}
