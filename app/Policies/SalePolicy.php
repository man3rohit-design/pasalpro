<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->role->isStaff()) {
            return true;
        }

        return $sale->customer !== null && $sale->customer->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Cashier);
    }

    public function update(User $user, Sale $sale): bool
    {
        return false;
    }

    public function delete(User $user, Sale $sale): bool
    {
        return false;
    }

    public function fulfil(User $user, Sale $sale): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Cashier)
            && $sale->channel === SaleChannel::Online
            && $sale->status->isOpen();
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager)
            && $sale->status !== SaleStatus::Cancelled;
    }
}
