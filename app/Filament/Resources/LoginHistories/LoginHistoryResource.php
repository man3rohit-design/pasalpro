<?php

declare(strict_types=1);

namespace App\Filament\Resources\LoginHistories;

use App\Enums\UserRole;
use App\Filament\Resources\LoginHistories\Pages\ManageLoginHistories;
use App\Models\LoginHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LoginHistoryResource extends Resource
{
    protected static ?string $model = LoginHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Login history';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('logged_in_at')
                    ->label('Signed in')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('user.role')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('ip_address')
                    ->label('IP address'),
                TextColumn::make('user_agent')
                    ->label('Browser')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('logged_in_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->options(UserRole::class)
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, string $role) => $query->whereRelation('user', 'role', $role),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoginHistories::route('/'),
        ];
    }
}
