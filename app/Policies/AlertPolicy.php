<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function view(User $user, Alert $alert): bool
    {
        return $user->role->isStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Alert $alert): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Manager, UserRole::Storekeeper)
            && $alert->resolved_at === null;
    }

    public function delete(User $user, Alert $alert): bool
    {
        return false;
    }
}
