<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper)
            && $purchaseOrder->status === PurchaseOrderStatus::Pending;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin)
            && $purchaseOrder->status === PurchaseOrderStatus::Pending;
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager)
            && $purchaseOrder->status === PurchaseOrderStatus::Pending;
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper)
            && $purchaseOrder->status === PurchaseOrderStatus::Approved;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager)
            && in_array($purchaseOrder->status, [PurchaseOrderStatus::Pending, PurchaseOrderStatus::Approved], true);
    }
}
