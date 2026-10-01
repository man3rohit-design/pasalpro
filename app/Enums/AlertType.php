<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AlertType: string implements HasColor, HasLabel
{
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';

    public function getLabel(): string
    {
        return str($this->name)->headline()->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LowStock   => 'warning',
            self::OutOfStock => 'danger',
        };
    }
}
