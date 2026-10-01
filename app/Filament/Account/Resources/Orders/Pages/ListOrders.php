<?php

declare(strict_types=1);

namespace App\Filament\Account\Resources\Orders\Pages;

use App\Filament\Account\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
