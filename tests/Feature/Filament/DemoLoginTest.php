<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('offers a one click login for each staff role on the admin login page', function (): void {
    Filament::setCurrentPanel('admin');
    $manager = User::factory()->role(UserRole::Manager)->create();
    $cashier = User::factory()->role(UserRole::Cashier)->create();
    $customer = User::factory()->create();

    Livewire::test(Login::class)
        ->assertSee('Demo accounts')
        ->assertActionVisible(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$manager->id, 'content'))
        ->assertActionVisible(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$cashier->id, 'content'))
        ->assertActionDoesNotExist(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$customer->id, 'content'));
});

it('fills in the credentials and signs the demo user in', function (): void {
    Filament::setCurrentPanel('admin');
    $manager = User::factory()->role(UserRole::Manager)->create();

    Livewire::test(Login::class)
        ->callAction(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$manager->id, 'content'))
        ->assertSet('data.email', $manager->email)
        ->assertRedirect();

    $this->assertAuthenticatedAs($manager);
});

it('offers the customer account on the shop login page', function (): void {
    Filament::setCurrentPanel('account');
    $customer = User::factory()->create();
    $admin = User::factory()->role(UserRole::Admin)->create();

    Livewire::test(Login::class)
        ->assertActionVisible(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$customer->id, 'content'))
        ->assertActionDoesNotExist(TestAction::make('demoLogin')->schemaComponent('demoLogin'.$admin->id, 'content'));
});

it('hides demo accounts in production', function (): void {
    Filament::setCurrentPanel('admin');
    User::factory()->role(UserRole::Admin)->create();
    app()['env'] = 'production';

    Livewire::test(Login::class)->assertDontSee('Demo accounts');
});
