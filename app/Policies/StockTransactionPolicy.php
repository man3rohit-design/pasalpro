<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StockTransaction;
use App\Models\User;

class StockTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function view(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StockTransaction $stockTransaction): bool
    {
        return false;
    }

    public function delete(User $user, StockTransaction $stockTransaction): bool
    {
        return false;
    }
}
