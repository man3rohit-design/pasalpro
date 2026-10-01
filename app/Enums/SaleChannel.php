<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SaleChannel: string implements HasColor, HasLabel
{
    case Counter = 'counter';
    case Online = 'online';

    public function getLabel(): string
    {
        return match ($this) {
            self::Counter => 'In store',
            self::Online  => 'Online',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Counter => 'gray',
            self::Online  => 'info',
        };
    }
}
