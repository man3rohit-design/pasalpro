<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\PurchaseOrder;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
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
                                TableColumn::make('Unit cost'),
                                TableColumn::make('Line total'),
                            ])
                            ->schema([
                                TextEntry::make('product.name'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_cost')->money(),
                                TextEntry::make('line_total')->money(),
                            ]),
                        TextEntry::make('total')
                            ->label('Order total')
                            ->money()
                            ->weight('bold'),
                    ]),
                Section::make('Order')
                    ->schema([
                        TextEntry::make('reference'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('supplier.name'),
                        TextEntry::make('supplier.phone')
                            ->label('Supplier phone'),
                        TextEntry::make('ordered_on')
                            ->date(),
                        TextEntry::make('expected_on')
                            ->label('Expected delivery')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('Raised by')
                            ->placeholder('-'),
                        TextEntry::make('approvedBy.name')
                            ->label('Approved by')
                            ->helperText(fn (PurchaseOrder $record): ?string => $record->approved_at?->format('M j, Y g:i A'))
                            ->placeholder('Awaiting approval'),
                        TextEntry::make('receivedBy.name')
                            ->label('Received by')
                            ->helperText(fn (PurchaseOrder $record): ?string => $record->received_at?->format('M j, Y g:i A'))
                            ->placeholder('Not received yet'),
                        TextEntry::make('notes')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
