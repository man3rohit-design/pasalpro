<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Models\Sale;
use App\Models\SaleItem;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class SalesReport extends ReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Sales report';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('period'),
                TextColumn::make('bills'),
                TextColumn::make('items_sold')
                    ->label('Items sold'),
                TextColumn::make('gross_sales')
                    ->money(),
                TextColumn::make('discounts')
                    ->money(),
                TextColumn::make('net_sales')
                    ->money()
                    ->weight('bold'),
                TextColumn::make('online_sales')
                    ->money()
                    ->toggleable(),
                TextColumn::make('cost_of_goods')
                    ->label('Cost of goods')
                    ->money()
                    ->toggleable(),
                TextColumn::make('gross_profit')
                    ->money(),
                TextColumn::make('margin')
                    ->suffix('%'),
            ])
            ->filters([
                static::periodFilter(),
                SelectFilter::make('group_by')
                    ->options([
                        'day'   => 'Day',
                        'month' => 'Month',
                    ])
                    ->default('day')
                    ->selectablePlaceholder(false),
            ])
            ->paginated(false);
    }

    /**
     * @return array<string, string>
     */
    public function getSummary(): array
    {
        $rows = $this->rows();
        $netSales = (float) $rows->sum('net_sales');
        $grossProfit = (float) $rows->sum('gross_profit');

        return [
            'Net sales'    => 'Rs. '.Number::format($netSales, 2, locale: 'en_IN'),
            'Bills'        => Number::format($rows->sum('bills'), locale: 'en_IN'),
            'Gross profit' => 'Rs. '.Number::format($grossProfit, 2, locale: 'en_IN'),
            'Margin'       => $netSales > 0 ? Number::format($grossProfit / $netSales * 100, 1).'%' : '-',
        ];
    }

    protected function rows(): Collection
    {
        [$from, $until] = $this->selectedPeriod();
        $periodLength = ($this->tableFilters['group_by']['value'] ?? 'day') === 'month' ? 7 : 10;

        $sales = Sale::completed()
            ->whereBetween('sold_at', [$from, $until])
            ->selectRaw('substr(sold_at, 1, ?) as period', [$periodLength])
            ->selectRaw('count(*) as bills, sum(subtotal) as gross_sales, sum(discount) as discounts, sum(total) as net_sales')
            ->selectRaw("sum(case when channel = 'online' then total else 0 end) as online_sales")
            ->groupBy('period')
            ->orderByDesc('period')
            ->get();

        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $until])
            ->selectRaw('substr(sales.sold_at, 1, ?) as period', [$periodLength])
            ->selectRaw('sum(sale_items.quantity) as items_sold')
            ->addSelect(DB::raw('sum(sale_items.unit_cost * sale_items.quantity) as cost_of_goods'))
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        return $sales->mapWithKeys(function (Sale $row) use ($items, $periodLength): array {
            $netSales = (float) $row->getAttribute('net_sales');
            $costOfGoods = (float) ($items[$row->getAttribute('period')]?->getAttribute('cost_of_goods') ?? 0);
            $period = $row->getAttribute('period');

            return [$period => [
                'period'        => $periodLength === 7 ? date('F Y', strtotime($period.'-01')) : date('D, M j Y', strtotime($period)),
                'bills'         => (int) $row->getAttribute('bills'),
                'items_sold'    => (int) ($items[$period]?->getAttribute('items_sold') ?? 0),
                'gross_sales'   => round((float) $row->getAttribute('gross_sales'), 2),
                'discounts'     => round((float) $row->getAttribute('discounts'), 2),
                'net_sales'     => round($netSales, 2),
                'online_sales'  => round((float) $row->getAttribute('online_sales'), 2),
                'cost_of_goods' => round($costOfGoods, 2),
                'gross_profit'  => round($netSales - $costOfGoods, 2),
                'margin'        => $netSales > 0 ? round(($netSales - $costOfGoods) / $netSales * 100, 1) : 0,
            ]];
        });
    }
}
