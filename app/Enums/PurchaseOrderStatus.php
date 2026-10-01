<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending   => 'warning',
            self::Approved  => 'info',
            self::Received  => 'success',
            self::Cancelled => 'gray',
        };
    }
}
