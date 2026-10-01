<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    public function getTabs(): array
    {
        $openOnlineStatuses = [SaleStatus::Pending, SaleStatus::Confirmed, SaleStatus::OutForDelivery];

        return [
            'all'     => Tab::make('All sales'),
            'counter' => Tab::make('In store')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('channel', SaleChannel::Counter)),
            'online' => Tab::make('Online orders')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('channel', SaleChannel::Online)),
            'to_fulfil' => Tab::make('To fulfil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('channel', SaleChannel::Online)->whereIn('status', $openOnlineStatuses))
                ->badge(Sale::where('channel', SaleChannel::Online)->whereIn('status', $openOnlineStatuses)->count() ?: null)
                ->badgeColor('warning'),
        ];
    }
}
