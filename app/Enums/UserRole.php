<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Storekeeper = 'storekeeper';
    case Customer = 'customer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin       => 'Administrator',
            self::Manager     => 'Shop Manager',
            self::Cashier     => 'Cashier',
            self::Storekeeper => 'Storekeeper',
            self::Customer    => 'Customer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin       => 'danger',
            self::Manager     => 'warning',
            self::Cashier     => 'success',
            self::Storekeeper => 'info',
            self::Customer    => 'gray',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
