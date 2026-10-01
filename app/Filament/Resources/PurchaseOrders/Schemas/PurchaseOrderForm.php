<?php

declare(strict_types=1);

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(3)
                    ->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('ordered_on')
                            ->default(today())
                            ->required(),
                        DatePicker::make('expected_on')
                            ->label('Expected delivery')
                            ->afterOrEqual('ordered_on'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product')->markAsRequired(),
                                TableColumn::make('Quantity')->markAsRequired()->width('140px'),
                                TableColumn::make('Unit cost')->markAsRequired()->width('180px'),
                                TableColumn::make('Line total')->width('160px'),
                            ])
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('unit_cost', Product::find($state)?->cost_price)),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(12)
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('unit_cost')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),
                                TextEntry::make('line_total')
                                    ->state(fn (Get $get): string => Number::currency((float) $get('quantity') * (float) $get('unit_cost'), 'NPR', 'en_IN')),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add product'),
                        TextEntry::make('order_total')
                            ->label('Order total')
                            ->weight('bold')
                            ->state(fn (Get $get): string => Number::currency(
                                collect($get('items'))->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0)),
                                'NPR',
                                'en_IN',
                            )),
                    ]),
            ]);
    }
}
