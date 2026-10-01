<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Login extends BaseLogin
{
    private const DEMO_PASSWORD = 'password';

    public function content(Schema $schema): Schema
    {
        $schema = parent::content($schema);

        return $schema->components([
            ...$schema->getComponents(),
            Section::make('Demo accounts')
                ->description('Sign in with one click. Not shown in production.')
                ->compact()
                ->visible(fn (): bool => ! app()->isProduction())
                ->columns(2)
                ->schema($this->demoLoginButtons()),
        ]);
    }

    /**
     * @return list<Actions>
     */
    private function demoLoginButtons(): array
    {
        $roles = Filament::getCurrentPanel()?->getId() === 'admin'
            ? [UserRole::Admin, UserRole::Manager, UserRole::Cashier, UserRole::Storekeeper]
            : [UserRole::Customer];

        return User::where('is_active', true)
            ->whereIn('role', $roles)
            ->orderBy('id')
            ->get()
            ->unique(fn (User $user): string => $user->role->value)
            ->sortBy(fn (User $user): int|false => array_search($user->role, $roles, true))
            ->map(fn (User $user): Actions => Actions::make([
                Action::make('demoLogin')
                    ->label($user->role->getLabel())
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->tooltip($user->email)
                    ->color('gray')
                    ->action(fn () => $this->loginAs($user)),
            ])
                ->key('demoLogin'.$user->id)
                ->fullWidth())
            ->values()
            ->all();
    }

    private function loginAs(User $user): void
    {
        $this->form->fill([
            'email'    => $user->email,
            'password' => self::DEMO_PASSWORD,
        ]);

        if ($this->authenticate() instanceof LoginResponse) {
            $this->redirectIntended(Filament::getUrl());
        }
    }
}
