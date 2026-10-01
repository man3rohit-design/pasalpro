<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

abstract class ReportPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(UserRole::Admin, UserRole::Manager) ?? false;
    }

    abstract protected function rows(): Collection;

    protected static function periodFilter(): Filter
    {
        return Filter::make('period')
            ->schema([
                DatePicker::make('from')
                    ->default(today()->subDays(29)),
                DatePicker::make('until')
                    ->default(today()),
            ])
            ->indicateUsing(fn (array $data): string => 'Period: '.Date::parse($data['from'] ?? today()->subDays(29))->format('M j, Y').' to '.Date::parse($data['until'] ?? today())->format('M j, Y'));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function selectedPeriod(): array
    {
        $period = $this->tableFilters['period'] ?? [];

        return [
            CarbonImmutable::parse($period['from'] ?? today()->subDays(29))->startOfDay(),
            CarbonImmutable::parse($period['until'] ?? today())->endOfDay(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->alpineClickHandler('window.print()'),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    private function downloadCsv(): StreamedResponse
    {
        $columns = collect($this->getTable()->getColumns())->reject(fn (Column $column): bool => $column->isHidden());
        $fileName = str($this->getTitle())->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($columns): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns->map(fn (Column $column): string => (string) $column->getLabel())->all());

            foreach ($this->rows() as $row) {
                fputcsv($output, $columns->map(fn (Column $column): mixed => $row[$column->getName()] ?? '')->all());
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
