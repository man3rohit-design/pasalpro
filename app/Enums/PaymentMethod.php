<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Cash = 'cash';
    case Card = 'card';
    case Esewa = 'esewa';
    case Khalti = 'khalti';
    case Fonepay = 'fonepay';
    case CashOnDelivery = 'cash_on_delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash           => 'Cash',
            self::Card           => 'Card',
            self::Esewa          => 'eSewa',
            self::Khalti         => 'Khalti',
            self::Fonepay        => 'Fonepay QR',
            self::CashOnDelivery => 'Cash on delivery',
        };
    }
}
