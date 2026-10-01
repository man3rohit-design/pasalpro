<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Schemas;

use App\Enums\SaleChannel;
use App\Models\Sale;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
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
                        TextEntry::make('subtotal')
                            ->money()
                            ->inlineLabel(),
                        TextEntry::make('discount')
                            ->money()
                            ->inlineLabel(),
                        TextEntry::make('total')
                            ->money()
                            ->weight('bold')
                            ->inlineLabel(),
                    ]),
                Section::make('Sale')
                    ->schema([
                        TextEntry::make('reference'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('channel')
                            ->badge(),
                        TextEntry::make('sold_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('customer.name')
                            ->placeholder('Walk-in customer'),
                        TextEntry::make('soldBy.name')
                            ->label('Cashier')
                            ->placeholder('-'),
                        TextEntry::make('payment_method'),
                        TextEntry::make('payment_status')
                            ->badge(),
                        TextEntry::make('payments.transaction_reference')
                            ->label('Payment reference')
                            ->placeholder('-'),
                        TextEntry::make('invoice.number')
                            ->label('Invoice')
                            ->placeholder('Issued on completion'),
                    ]),
                Section::make('Delivery')
                    ->columnSpan(2)
                    ->columns(3)
                    ->visible(fn (Sale $record): bool => $record->channel === SaleChannel::Online)
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
}
