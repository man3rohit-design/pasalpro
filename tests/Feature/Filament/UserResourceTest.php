<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;

it('lets an administrator add a cashier who can then sign in to the panel', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Admin)->create());

    Livewire::test(ManageUsers::class)
        ->callAction('create', [
            'name'      => 'Ramesh Basnet',
            'email'     => 'ramesh@pasalpro.test',
            'phone'     => '9812345678',
            'role'      => UserRole::Cashier,
            'password'  => 'secret-pass',
            'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    $cashier = User::where('email', 'ramesh@pasalpro.test')->sole();

    expect($cashier->role)->toBe(UserRole::Cashier)
        ->and(Hash::check('secret-pass', $cashier->password))->toBeTrue();
});

it('keeps the current password when the field is left blank on edit', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Admin)->create());
    $cashier = User::factory()->role(UserRole::Cashier)->create();
    $originalPassword = $cashier->password;

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($cashier), ['name' => 'Pooja M.', 'password' => ''])
        ->assertHasNoFormErrors();

    expect($cashier->fresh())
        ->name->toBe('Pooja M.')
        ->password->toBe($originalPassword);
});

it('prevents an administrator from deleting their own account', function (): void {
    $admin = User::factory()->role(UserRole::Admin)->create();
    $this->actingAs($admin);

    Livewire::test(ManageUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($admin));
});

it('prevents deleting a category that still has products', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Admin)->create());
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    Livewire::test(ManageCategories::class)
        ->assertActionHidden(TestAction::make('delete')->table($category));
});
