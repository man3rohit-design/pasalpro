<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockTransactionType: string implements HasColor, HasLabel
{
    case OpeningStock = 'opening_stock';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case SaleCancelled = 'sale_cancelled';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OpeningStock  => 'gray',
            self::Purchase      => 'success',
            self::Sale          => 'danger',
            self::SaleCancelled => 'info',
            self::Adjustment    => 'warning',
        };
    }
}
