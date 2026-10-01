<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SaleStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending        => 'warning',
            self::Confirmed      => 'info',
            self::OutForDelivery => 'primary',
            self::Completed      => 'success',
            self::Cancelled      => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::OutForDelivery], true);
    }
}
