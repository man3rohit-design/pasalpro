<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

it('lets active staff into the admin panel', function (UserRole $role): void {
    $this->actingAs(User::factory()->role($role)->create())
        ->get('/admin')
        ->assertOk();
})->with([
    UserRole::Admin,
    UserRole::Manager,
    UserRole::Cashier,
    UserRole::Storekeeper,
]);

it('forbids customers from the admin panel', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Customer)->create())
        ->get('/admin')
        ->assertForbidden();
});

it('forbids deactivated staff from the admin panel', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create(['is_active' => false]))
        ->get('/admin')
        ->assertForbidden();
});
