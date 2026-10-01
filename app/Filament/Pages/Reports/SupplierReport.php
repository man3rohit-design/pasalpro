<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\PurchaseOrderStatus;
use App\Models\Supplier;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class SupplierReport extends ReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Supplier report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('supplier')
                    ->wrap(),
                TextColumn::make('phone'),
                TextColumn::make('products')
                    ->label('Products supplied'),
                TextColumn::make('orders_received')
                    ->label('Orders received'),
                TextColumn::make('purchased')
                    ->label('Purchased')
                    ->money(),
                TextColumn::make('open_orders')
                    ->label('Open orders'),
                TextColumn::make('open_value')
                    ->label('Open order value')
                    ->money(),
                TextColumn::make('last_delivery')
                    ->label('Last delivery')
                    ->placeholder('-'),
            ])
            ->filters([
                static::periodFilter(),
            ])
            ->paginated(false);
    }

    /**
     * @return array<string, string>
     */
    public function getSummary(): array
    {
        $rows = $this->rows();

        return [
            'Suppliers'        => (string) $rows->count(),
            'Orders received'  => (string) $rows->sum('orders_received'),
            'Purchased'        => 'Rs. '.Number::format((float) $rows->sum('purchased'), 2, locale: 'en_IN'),
            'Open order value' => 'Rs. '.Number::format((float) $rows->sum('open_value'), 2, locale: 'en_IN'),
        ];
    }

    protected function rows(): Collection
    {
        [$from, $until] = $this->selectedPeriod();
        $received = fn (Builder $query) => $query->where('status', PurchaseOrderStatus::Received)->whereBetween('received_at', [$from, $until]);
        $open = fn (Builder $query) => $query->whereIn('status', [PurchaseOrderStatus::Pending, PurchaseOrderStatus::Approved]);

        return Supplier::query()
            ->withCount(['products', 'purchaseOrders as orders_received' => $received, 'purchaseOrders as open_orders' => $open])
            ->withSum(['purchaseOrders as purchased' => $received], 'total')
            ->withSum(['purchaseOrders as open_value' => $open], 'total')
            ->withMax(['purchaseOrders as last_delivery' => $received], 'received_at')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Supplier $supplier): array => [$supplier->id => [
                'supplier'        => $supplier->name,
                'phone'           => $supplier->phone,
                'products'        => $supplier->getAttribute('products_count'),
                'orders_received' => $supplier->getAttribute('orders_received'),
                'purchased'       => round((float) $supplier->getAttribute('purchased'), 2),
                'open_orders'     => $supplier->getAttribute('open_orders'),
                'open_value'      => round((float) $supplier->getAttribute('open_value'), 2),
                'last_delivery'   => $supplier->getAttribute('last_delivery') ? date('M j, Y', strtotime((string) $supplier->getAttribute('last_delivery'))) : null,
            ]]);
    }
}
