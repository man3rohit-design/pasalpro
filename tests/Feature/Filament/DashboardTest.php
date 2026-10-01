<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\SalesOverview;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('shows monthly sales and profit to managers', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Manager)->create());

    Livewire::test(SalesOverview::class)
        ->assertSee("Today's sales")
        ->assertSee('Sales this month')
        ->assertSee('Gross profit');
});

it('hides profit figures from cashiers', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());

    Livewire::test(SalesOverview::class)
        ->assertSee("Today's sales")
        ->assertDontSee('Gross profit');
});

it('lists products that are running low', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Storekeeper)->create());
    $low = Product::factory()->withStock(2, reorderLevel: 10)->create();
    $healthy = Product::factory()->withStock(50, reorderLevel: 10)->create();

    Livewire::test(LowStockProducts::class)
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$healthy]);
});

it('does not show the low stock widget to cashiers', function (): void {
    $this->actingAs(User::factory()->role(UserRole::Cashier)->create());

    expect(LowStockProducts::canView())->toBeFalse();
});
