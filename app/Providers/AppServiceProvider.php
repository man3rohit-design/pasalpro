<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => Color::Emerald,
        ]);

        Table::configureUsing(fn (Table $table): Table => $table
            ->defaultCurrency('NPR')
            ->defaultNumberLocale('en_IN')
            ->defaultDateDisplayFormat('M j, Y')
            ->defaultDateTimeDisplayFormat('M j, Y g:i A'));

        Schema::configureUsing(fn (Schema $schema): Schema => $schema
            ->defaultCurrency('NPR')
            ->defaultNumberLocale('en_IN')
            ->defaultDateDisplayFormat('M j, Y')
            ->defaultDateTimeDisplayFormat('M j, Y g:i A'));
    }
}
