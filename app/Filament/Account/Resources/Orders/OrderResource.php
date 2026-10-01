<?php

declare(strict_types=1);

namespace App\Filament\Account\Resources\Orders;

use App\Enums\SaleChannel;
use App\Filament\Account\Resources\Orders\Pages\ListOrders;
use App\Filament\Account\Resources\Orders\Pages\ViewOrder;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $modelLabel = 'order';

    protected static ?string $navigationLabel = 'My orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static bool $shouldSkipAuthorization = true;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('channel', SaleChannel::Online)
            ->whereRelation('customer', 'user_id', auth()->id());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Items')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product'),
                                TableColumn::make('Quantity'),
                                TableColumn::make('Price'),
                                TableColumn::make('Amount'),
                            ])
                            ->schema([
                                TextEntry::make('product_name'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price')->money(),
                                TextEntry::make('line_total')->money(),
                            ]),
                        TextEntry::make('total')
                            ->money()
                            ->weight('bold')
                            ->inlineLabel(),
                    ]),
                Section::make('Order status')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->size('lg'),
                        TextEntry::make('sold_at')
                            ->label('Placed')
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->label('Delivered')
                            ->dateTime()
                            ->placeholder('Not yet'),
                        TextEntry::make('payment_method')
                            ->label('Payment'),
                        TextEntry::make('payment_status')
                            ->badge(),
                    ]),
                Section::make('Delivery')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('delivery_name')
                            ->label('Name'),
                        TextEntry::make('delivery_phone')
                            ->label('Phone'),
                        TextEntry::make('delivery_address')
                            ->label('Address'),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->weight('medium'),
                TextColumn::make('sold_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                TextColumn::make('total')
                    ->money(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('sold_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders you place in the shop will show up here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view'  => ViewOrder::route('/{record}'),
        ];
    }
}
