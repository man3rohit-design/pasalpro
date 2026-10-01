<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Alerts\AlertResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Alert;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todaySales = $this->netSalesBetween(today(), now());
        $yesterdaySales = $this->netSalesBetween(today()->subDay(), now()->subDay());
        $monthSales = $this->netSalesBetween(today()->startOfMonth(), now());
        $dailyTrend = collect(range(6, 0))->map(fn (int $daysAgo): float => $this->netSalesBetween(today()->subDays($daysAgo), today()->subDays($daysAgo)->endOfDay()));

        $stats = [
            Stat::make("Today's sales", $this->rupees($todaySales))
                ->description(Sale::completed()->where('sold_at', '>=', today())->count().' bills · yesterday by now '.$this->rupees($yesterdaySales))
                ->descriptionIcon($todaySales >= $yesterdaySales ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($dailyTrend->all())
                ->color($todaySales >= $yesterdaySales ? 'success' : 'warning'),
            Stat::make('Online orders to fulfil', (string) Sale::where('channel', SaleChannel::Online)
                ->whereIn('status', [SaleStatus::Pending, SaleStatus::Confirmed, SaleStatus::OutForDelivery])
                ->count())
                ->description('Pending, confirmed or out for delivery')
                ->url(SaleResource::getUrl('index', ['activeTab' => 'to_fulfil']))
                ->color('info'),
            Stat::make('Low stock alerts', (string) Alert::open()->count())
                ->description('Products at or below their reorder level')
                ->url(AlertResource::getUrl())
                ->color('danger'),
        ];

        if (auth()->user()?->hasRole(UserRole::Admin, UserRole::Manager)) {
            $monthCost = SaleItem::query()
                ->whereHas('sale', fn (Builder $sale) => $sale->where('status', SaleStatus::Completed)->whereBetween('sold_at', [today()->startOfMonth(), now()]))
                ->sum(DB::raw('unit_cost * quantity'));

            array_splice($stats, 1, 0, [
                Stat::make('Sales this month', $this->rupees($monthSales))
                    ->description('Gross profit '.$this->rupees($monthSales - (float) $monthCost)),
            ]);
        }

        return $stats;
    }

    private function netSalesBetween(CarbonInterface $from, CarbonInterface $until): float
    {
        return (float) Sale::completed()->whereBetween('sold_at', [$from, $until])->sum('total');
    }

    private function rupees(float $amount): string
    {
        return 'Rs. '.Number::format($amount, precision: 0, locale: 'en_IN');
    }
}
