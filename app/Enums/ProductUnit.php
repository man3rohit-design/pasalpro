<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductUnit: string implements HasLabel
{
    case Piece = 'pcs';
    case Packet = 'pkt';
    case Bottle = 'btl';
    case Kilogram = 'kg';
    case Litre = 'ltr';
    case Box = 'box';

    public function getLabel(): string
    {
        return match ($this) {
            self::Piece    => 'Piece',
            self::Packet   => 'Packet',
            self::Bottle   => 'Bottle',
            self::Kilogram => 'Kilogram',
            self::Litre    => 'Litre',
            self::Box      => 'Box',
        };
    }
}
