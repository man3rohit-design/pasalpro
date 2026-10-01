<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductUnit;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Product details')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                        if ($operation === 'create') {
                                            $set('slug', str($state)->slug()->toString());
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('supplier_id')
                                    ->label('Preferred supplier')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                FileUpload::make('image_path')
                                    ->label('Photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Pricing')
                            ->columns(3)
                            ->schema([
                                TextInput::make('cost_price')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('selling_price')
                                    ->numeric()
                                    ->prefix('Rs.')
                                    ->minValue(0)
                                    ->gte('cost_price')
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('margin')
                                    ->label('Margin')
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(fn (Get $get): string => self::margin($get('cost_price'), $get('selling_price'))),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Identification')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('barcode')
                                    ->required()
                                    ->length(13)
                                    ->unique(ignoreRecord: true)
                                    ->default(fn (): string => Product::generateBarcode())
                                    ->helperText('Scan the EAN-13 barcode on the pack, or generate one for loose items.')
                                    ->suffixAction(
                                        Action::make('generateBarcode')
                                            ->icon(Heroicon::OutlinedArrowPath)
                                            ->tooltip('Generate a new barcode')
                                            ->action(fn (Set $set): mixed => $set('barcode', Product::generateBarcode())),
                                    ),
                                Select::make('unit')
                                    ->options(ProductUnit::class)
                                    ->default(ProductUnit::Piece)
                                    ->required(),
                                DatePicker::make('expiry_date')
                                    ->label('Nearest expiry'),
                            ]),
                        Section::make('Stock')
                            ->schema([
                                TextInput::make('opening_stock')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->dehydrated(false)
                                    ->visibleOn('create'),
                                Group::make()
                                    ->relationship('inventory')
                                    ->schema([
                                        TextInput::make('reorder_level')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->default(10)
                                            ->required()
                                            ->helperText('A low stock alert is raised at or below this quantity.'),
                                    ]),
                                Toggle::make('is_active')
                                    ->label('Available for sale')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Feature on shop home page'),
                            ]),
                    ]),
            ]);
    }

    private static function margin(mixed $costPrice, mixed $sellingPrice): string
    {
        if (! is_numeric($costPrice) || ! is_numeric($sellingPrice) || (float) $sellingPrice <= 0) {
            return '-';
        }

        return number_format(((float) $sellingPrice - (float) $costPrice) / (float) $sellingPrice * 100, 1);
    }
}
