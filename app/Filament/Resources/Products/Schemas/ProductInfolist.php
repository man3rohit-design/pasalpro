<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Product details')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('supplier.name')
                            ->label('Preferred supplier')
                            ->placeholder('-'),
                        TextEntry::make('unit'),
                        TextEntry::make('cost_price')
                            ->money(),
                        TextEntry::make('selling_price')
                            ->money(),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Stock')
                    ->schema([
                        ImageEntry::make('image_path')
                            ->hiddenLabel()
                            ->disk('public')
                            ->visible(fn (Product $record): bool => filled($record->image_path)),
                        TextEntry::make('inventory.quantity')
                            ->label('On hand')
                            ->badge()
                            ->color(fn (Product $record): string => $record->inventory->isLowStock() ? 'danger' : 'success'),
                        TextEntry::make('inventory.reorder_level')
                            ->label('Reorder level'),
                        TextEntry::make('inventory.last_restocked_at')
                            ->label('Last restocked')
                            ->since()
                            ->placeholder('Never'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->copyable(),
                        TextEntry::make('barcode')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable(),
                        TextEntry::make('expiry_date')
                            ->label('Nearest expiry')
                            ->date()
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label('Available for sale')
                            ->boolean(),
                    ]),
            ]);
    }
}
