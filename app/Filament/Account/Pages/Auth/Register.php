<?php

declare(strict_types=1);

namespace App\Filament\Account\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                TextInput::make('phone')
                    ->label('Mobile number')
                    ->tel()
                    ->required()
                    ->regex('/^9[678]\d{8}$/')
                    ->validationMessages(['regex' => 'Enter a 10 digit Nepali mobile number, e.g. 9841234567.']),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = User::create([...$data, 'role' => UserRole::Customer]);

        $user->customer()->create([
            'name'  => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ]);

        return $user;
    }
}
