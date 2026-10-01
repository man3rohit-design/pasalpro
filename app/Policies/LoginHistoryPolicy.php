<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LoginHistory;
use App\Models\User;

class LoginHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, LoginHistory $loginHistory): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LoginHistory $loginHistory): bool
    {
        return false;
    }

    public function delete(User $user, LoginHistory $loginHistory): bool
    {
        return false;
    }
}
